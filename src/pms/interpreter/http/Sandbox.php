<?php

namespace pms\interpreter\http;

use pms\app\HttpApp;
use pms\contract\AppInterface;
use pms\app\HttpMiddlewareApp;
use pms\Container;
use pms\facade\BootOptions;
use pms\facade\Config;
use pms\hook\HttpLifecycleHook;
use pms\HttpExceptionHandle;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;

use pms\exception\SystemException;
use pms\exception\ClassNotFoundException;
use pms\exception\CliModeForcedInterruptException;

use pms\facade\Path;
use pms\inject\HttpRouteInject;
use pms\interpreter\http\sandbox\HttpRoute;
use ReflectionClass;
use Throwable;


class Sandbox extends Container
{
    protected HttpRequestInject $request;
    protected HttpResponseInject $response;
    protected HttpRouteInject $route;
    protected array $middlewares = [];
    protected string $contentType = JSON_CONTENT_TYPE;

    /**
     * @var bool 当前是否为转发请求
     */
    protected bool $isForward = false;

    public function __construct(HttpRequestInject $request, HttpResponseInject $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function run(?string $forward = null): mixed
    {
        if ($forward !== null) {
            $this->isForward = true;
        }

        $this->initCors();
        try {

            if (!$this->isForward) {
                set_error_handler('HttpCustomErrorHandler');
            }

            $this->route = new HttpRoute($this->request->pathinfo(),$forward);

            if (!$this->isForward) {
                if ($this->request->isOptions()) {
                    $this->response->end();
                    return true;
                }
                HttpLifecycleHook::run(LIFECYCLE_SANDBOX_CREATED,
                    $this->request,
                    $this->response,
                    $this->route,
                );
                if ($this->route->inStatic) {
                    $this->sendFile($this->request->pathinfo());
                    return true;
                }
                if (!$this->route->inApp) {
                    $this->response->status(500, "Gateway Not Found");
                    $this->response->end('Gateway Not Found');
                    return true;
                }

                if (!$this->route->inTerminal) {
                    $this->response->status(500, "Gateway Not Found");
                    $this->response->end('Gateway Not Found');
                    return true;
                }
                $this->request->init();
            }

            $this->route->activate($this->request, $this->response);

            $this->putInject();
            return $this->execute();
        } catch (Throwable $e) {
            // 跳过php系统内部异常，转交给 register_shutdown_function
            $error = error_get_last();
            if ($error === null || $error['type'] !== E_WARNING) {
                if ($this->response->isWritable()) {
                    $this->response->header('Content-Type', $this->contentType);
                    $this->exceptionHandle($e);
                }
            }
            return false;
        }
    }

    protected function initCors(): void
    {
        if ($this->isForward) {
            return;
        }
        $responseHeader = config('http.cors', []);
        foreach ($responseHeader as $key => $value) {
            if (is_array($value)) {
                $value = join(',', $value);
            }
            $this->response->header($key, $value);
        }
    }

    protected function sendFile(string $pathinfo): void
    {
        $filePath = Path::getWebRoot($pathinfo);
        if (is_file($filePath)) {
            try {
                $this->response->header('Content-Type', mime_content_type($filePath));
                $this->response->header('Content-Length', (string)filesize($filePath));
                $content = file_get_contents($filePath);
                if ($content === false) {
                    $this->response->status(404);
                    $this->response->end();
                    return;
                }
                $this->response->end($content);
            } catch (Throwable $e) {
                $this->response->status(404);
                $this->response->end();
            }
        } else {
            $this->response->status(404);
            $this->response->end();
        }
    }

    protected function putInject(): void
    {
        $this->put(HttpRequestInject::class, $this->request);
        $this->put(HttpResponseInject::class, $this->response);
        $this->put(HttpRouteInject::class, $this->route);
    }


    protected function initInterpreterConfig(): void
    {
        $interpreterName = config('http.app.structure.package', 'http');
        $configName = config('http.app.structure.config', 'config');

        $interpreterConfigPath = Path::getConfig(
            'interpreter',
            $interpreterName
        );

        $interpreterAppConfigPath = Path::getConfig(
            'interpreter',
            $interpreterName,
            'app',
            $this->route->app,
        );

        if ($this->route->isStm) {
            $appConfigPath = Path::getApp(
                $this->route->app,
                $configName
            );
        } else {
            $appConfigPath = Path::getApp(
                $this->route->app,
                $this->route->terminal,
                $configName
            );
        }
        Config::fetchConfig([
            $interpreterConfigPath,
            $interpreterAppConfigPath,
            $appConfigPath
        ], true);
    }


    protected function middleware(ReflectionClass $interfaceClass): void
    {
        $middlewaresConfig = config('middleware', []);
        if (is_string($middlewaresConfig)) {
            $middlewaresConfig = [$middlewaresConfig];
        }

        $this->middlewares = array_unique([
            ...$this->middlewares,
            // 执行应用全局中间件
            ...$middlewaresConfig,
            // 执行接口独立中间件
            ...$interfaceClass->getProperty('middleware')->getDefaultValue(),
        ]);

        foreach ($this->middlewares as $item) {

            $this->runMiddleware($item, [
                $this->request,
                $this->response,
                $this->route,
                $interfaceClass,
            ]);
        }

    }

    /**
     * 执行中间件
     * @param string $middlewares
     * @param array  $args
     * @return void
     */
    protected function runMiddleware(string $middlewares, array $args): void
    {
        /**
         * @var $obj HttpMiddlewareApp
         */
        if (class_exists($middlewares)) {
            $mClass = $this->getClass($middlewares);
            $obj = $this->invokeClass($mClass, $args);
            $obj->entry();
            if ($mClass->hasMethod('callback')) {
                $obj->callback($this);
            }
        } else {
            throw new SystemException("middleware is not found:" . $middlewares, 503);
        }
    }

    protected function contentToString(mixed $result, string $contentType)
    {
        if (is_string($result)) {
            return $result;
        }
        return match ($contentType) {
            JSON_CONTENT_TYPE  => json_encode($result, 320),
            JSONP_CONTENT_TYPE => $this->request->get('callback', 'callback') . '(' . json_encode($result) . ')',
            XML_CONTENT_TYPE   => array_to_xml($result),
            default            => is_array($result) || is_object($result) ? json_encode($result, 320) : $result,
        };
    }

    protected function execute()
    {

        if (!class_exists($this->route->interfaceClass)) {
            throw new ClassNotFoundException($this->route->interfaceClass);
        }

        $class = $this->getClass($this->route->interfaceClass);
        if (!$class->isSubclassOf(HttpApp::class)) {
            throw new ClassNotFoundException($this->route->interfaceClass);
        }
        $this->contentType = $class->getProperty('contentType')->getDefaultValue();
        $this->initInterpreterConfig();

        if(!$this->isForward){
            HttpLifecycleHook::run(LIFECYCLE_SANDBOX_BOOT,
                $this->request,
                $this->response,
                $this->route,
                $class
            );
        }

        $this->middleware($class);
        /**
         * @var $obj HttpApp
         */
        $obj = $this->invokeClass($class);
        $obj->app = $this->route->app;
        $obj->terminal = $this->route->terminal;
        $obj->inForward = $this->isForward;
        if(!$this->isForward){
            HttpLifecycleHook::run(LIFECYCLE_SANDBOX_BOOTED,
                $this->request,
                $this->response,
                $this->route,
                $class,
                $obj
            );
        }


        if (method_exists($obj, '__prepare')) {
            $obj->__prepare();
        }

        /**
         * @var $obj AppInterface
         */
        $result = $obj->entry();

        if (method_exists($obj, '__teardown')) {
            $obj->__teardown();
        }

        if ($result === null && $class->hasProperty('resRaw')) {
            $result = $class->getProperty('resRaw')->getValue($obj);
        }
        if(!$this->isForward){
            if($class->hasProperty('contentType')){
                $this->contentType = $class->getProperty('contentType')->getValue($obj);
            }
            if ($this->response->isWritable()) {
                $result = $this->contentToString($result, $this->contentType);
                $this->response->header('Content-Type', $this->contentType);
                $this->response->end($result);
            }
            HttpLifecycleHook::run(LIFECYCLE_SANDBOX_RAN,
                $this->request,
                $this->response,
                $this->route,
                $class,
                $obj,
                $result
            );
        }
        return $result;
    }


    /**
     * 加载异常处理器
     * @param Throwable $e
     * @param bool      $inUser 是否使用应用内客制化处理器
     * @return void
     */
    protected function exceptionHandle(Throwable $e, bool $inUser = true): void
    {
        try {
            if (!($e instanceof CliModeForcedInterruptException)) {
                if ($inUser) {
                    $handleClass = $this->route->exceptionClass();
                } else {
                    $handleClass = HttpExceptionHandle::class;
                }

                $class = $this->getClass($handleClass);

                /**
                 * @var HttpExceptionHandle $obj
                 */
                $obj = $this->invokeClass($class, [
                    BootOptions::get_error_debug(),
                    $e,
                    function ($code) {
                        $this->response->status($code);
                    }
                ]);

                $content = $obj->getContent();
                $result = $this->contentToString($content, $this->contentType);
                if ($result === false) {
                    unset($content['trace']);
                    $result = $this->contentToString($content, $this->contentType);
                }
                $this->response->end($result);
            } else {
                $this->response->setStatusCode(500);
                $this->response->end('');
            }
        } catch (Throwable $e) {
            $this->exceptionHandle($e, false);
        }

    }

    public function __destruct()
    {
        HttpLifecycleHook::run(LIFECYCLE_SANDBOX_DESTRUCT);
    }
}
