<?php

namespace pms\interpreter\http;

use pms\contract\AppInterface;
use pms\app\HttpMiddlewareApp;
use pms\Container;
use pms\HttpExceptionHandle;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;

use pms\exception\SystemException;
use pms\exception\ClassNotFoundException;
use pms\exception\CliModeForcedInterruptException;

use pms\facade\Path;
use ReflectionClass;


class Sandbox extends Container
{
    protected HttpRequestInject $request;
    protected HttpResponseInject $response;
    protected array $middlewares = [];
    protected string $contentType = JSON_CONTENT_TYPE;

    protected string $app = '';

    public function __construct(HttpRequestInject $request, HttpResponseInject $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function run(): bool
    {
        try {
            $this->initCors();

            if ($this->request->isOptions()) {
                $this->response->end();
                return true;
            }

            set_error_handler('HttpCustomErrorHandler');

            if (!$this->inHttpApp()) {
                $this->sendFile($this->request->pathinfo());
                return true;
            }

            $this->request->init();
            $this->putInject();

            $data = $this->execute($this->getRealPathInfo(), function (ReflectionClass $class, AppInterface $obj) {
                $contentType = $class->getProperty('contentType');
                $this->contentType = $contentType->getValue($obj);
            });
            if ($this->response->isWritable()) {
                $data = $this->contentToString($data, $this->contentType);
                $this->response->header('Content-Type', $this->contentType);
                $this->response->end($data);
            }
            return true;
        } catch (\Throwable $e) {
            $this->response->header('Content-Type', $this->contentType);
            $this->exceptionHandle($e);
            return false;
        }
    }

    protected function inHttpApp(): bool
    {

        $pathinfo = $this->request->pathinfo();
        $apps = config('http.apps',[]);
        if (is_string($apps)) {
            $apps = [$apps];
        }

        $inApp = false;
        foreach ($apps as $app) {
            if (str_starts_with($pathinfo, '/' . $app)) {
                $inApp = true;
                $this->app = $app;
                break;
            }
        }

        return $inApp;
    }

    protected function initCors(): void{
        $responseHeader = config('http.cors', []);
        foreach ($responseHeader as $key => $value) {
            $this->response->header($key, $value);
        }
    }

    protected function sendFile(string $pathinfo): void
    {
        $filePath = Path::getWebRoot($pathinfo);
        if (is_file($filePath)) {
            try{
                $this->response->header('Content-Type', mime_content_type($filePath));
                $this->response->end(file_get_contents($filePath));
            }catch (\Throwable $e){
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
    }

    protected function getRealPathInfo(): string
    {
        $pathinfo = $this->request->pathinfo();
        $defaultPath = [
            '/' . $this->app,
            '/' . $this->app . "/",
        ];
        if (in_array($pathinfo, $defaultPath)) {
            $defaultController = config('http.default_controller', 'Index');
            $pathinfo = '/' . $this->app . '/' . $defaultController;
        }
        return $pathinfo;
    }


    protected function initMiddlewareConfig(): void{
        $config = [];
        $middlewarePath = Path::getApp($this->app . "/http/middleware.php");
        if (file_exists($middlewarePath)) {
            $middleware = include $middlewarePath;
            if (is_array($middleware)) {
                $config = $middleware;
            }
        }
        $this->middlewares = [
            ...$this->middlewares,
            ...$config,
        ];
    }

    protected function middleware(string $classNamespace, \Closure $callback = null): ReflectionClass
    {
        try {
            $actionClass = $this->getClass($classNamespace);
        } catch (\Throwable $e) {
            throw new ClassNotFoundException($classNamespace, $e);
        }
        $this->contentType = $actionClass->getProperty('contentType')->getDefaultValue();
        // 执行应用全局中间件
        $this->runMiddleware($this->middlewares, [
            $actionClass,
            $this->request,
            $this->app
        ]);
        // 执行应用独立中间件
        $actionMiddlewares = $actionClass->getProperty('middleware')->getDefaultValue();
        $this->runMiddleware($actionMiddlewares, [
            $actionClass,
            $this->request,
            $this->app
        ]);
        return $actionClass;
    }

    /**
     * 执行中间件
     * @param array|string $middlewares
     * @param array $args
     * @return void
     */
    protected function runMiddleware(array|string $middlewares, array $args): void
    {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }
        foreach ($middlewares as $item) {
            /**
             * @var $obj HttpMiddlewareApp
             */
            if (class_exists($item)) {
                $mClass = $this->getClass($item);
                $obj = $this->invokeClass($mClass, $args);
                $obj->entry();
                if ($mClass->hasMethod('callback')) {
                    $obj->callback($this);
                }
            } else {
                throw new SystemException("middleware is not found:" . $item,503);
            }
        }
    }

    protected function contentToString(mixed $data, string $contentType)
    {
        if (is_string($data)) {
            return $data;
        }
        return match ($contentType) {
            JSON_CONTENT_TYPE => json_encode($data, 320),
            JSONP_CONTENT_TYPE => $this->request->get('callback', 'callback') . '(' . json_encode($data) . ')',
            XML_CONTENT_TYPE => array_to_xml($data),
            default => is_array($data) || is_object($data) ? json_encode($data, 320) : $data,
        };
    }

    protected function execute(string $pathinfo, \Closure $callback = null)
    {
        $namespace = $this->pathinfoToNamespace($pathinfo);

        if (!class_exists($namespace)) {
            throw new ClassNotFoundException($namespace);
        }
        $this->initMiddlewareConfig();

        $class = $this->middleware($namespace, $callback);
        $obj = $this->invokeClass($class);
        $obj->app = $this->app;

        if(method_exists($obj,'__prepare')) {
            $obj->__prepare();
        }

        /**
         * @var $obj AppInterface
         */
        $data = $obj->entry();
        if ($data === null) {
            $data = $class->getProperty('resRaw')->getValue($obj);
        }
        if ($callback !== null) {
            $callback($class, $obj);
        }

        if(method_exists($obj,'__teardown')) {
            $obj->__teardown();
        }
        return $data;
    }

    protected function pathinfoToNamespace(string $pathinfo): string
    {
        $packageName = config('http.package_name', 'package');

        $pathinfo = str_replace(".", "\\", $pathinfo);
        $pathinfo = str_replace("//", "\\", $pathinfo);
        $pathinfo = str_replace("/", "\\", $pathinfo);
        $pathinfo = trim($pathinfo, "\\");
        $pathinfo = explode("\\", $pathinfo);
        $pathinfo = join("\\", [
            '',
            'app',
            ...array_slice($pathinfo, 0, 1),
            config('interpreter_name','http'),
            $packageName,
            ...array_slice($pathinfo, 1, count($pathinfo) - 2),
            ucfirst($pathinfo[count($pathinfo) - 1])
        ]);
        return $pathinfo;
    }

    /**
     * 加载异常处理器
     * @param \Throwable $e
     * @param bool $inUser 是否使用应用内客制化处理器
     * @return void
     */
    protected function exceptionHandle(\Throwable $e, bool $inUser = true): void{
        try {
            if (!($e instanceof CliModeForcedInterruptException)) {
                $userHandle = "\\app\\".config('interpreter_name','http')."\\$this->app\\HttpExceptionHandle";
                $systemHandle = "\\pms\\HttpExceptionHandle";
                $handle = $systemHandle;
                if ($inUser && class_exists($userHandle)) {
                    $handle = $userHandle;
                }
                $class = $this->getClass($handle);
                /**
                 * @var HttpExceptionHandle $obj
                 */
                $obj = $this->invokeClass($class, [
                    $e,
                    function ($code) {
                        $this->response->status($code);
                    }
                ]);
                $data = $this->contentToString($obj->getContent(), $this->contentType);
                $this->response->end($data);
            } else {
                $this->response->end('');
            }
        } catch (\Throwable $e) {
            // 如果客制化Handle异常，则抛出系统的异常
            $this->exceptionHandle($e, false);
        }

    }

}