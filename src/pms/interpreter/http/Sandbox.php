<?php

namespace pms\interpreter\http;

use pms\contract\AppInterface;
use pms\app\HttpMiddlewareApp;
use pms\Container;
use pms\facade\Config;
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
    protected string $terminal = '';
    protected string $interface = '';

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


            $this->analysisPathInfo();

            if (!$this->inApp()) {
                $this->sendFile($this->request->pathinfo());
                return true;
            }

            $this->request->init();
            $this->putInject();
            $data = $this->execute(function (ReflectionClass $class, AppInterface $obj) {
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


    protected function analysisPathInfo(){
        $pathinfo = $this->request->pathinfo();
        $arr = explode("/",$pathinfo);
        $this->app = config('http.default.app', 'index');
        $this->terminal = config('http.default.terminal', 'index');
        $this->interface = config('http.default.interface', 'Index');

        foreach ($arr as $key => $value){
            if ($value == '' || $value == '.' || $value == '..') {
                unset($arr[$key]);
            }
        }
        $arr = array_values($arr);
        switch (count($arr)){
            case 0:
                break;
            case 1:
                $this->app = $arr[0];
                break;
            case 2:
                $this->app = $arr[0];
                $this->terminal = $arr[1];
                break;
            default:
                $this->app = $arr[0];
                $this->terminal = $arr[1];
                $this->interface = join("\\",array_slice($arr, 2));
                break;
        }

    }



    protected function inApp(): bool{
        $apps = config('http.apps',[]);
        if (is_string($apps)) {
            $apps = [$apps];
        }
        return in_array($this->app, $apps);
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


    protected function initInterpreterConfig(): void{
        $interpreterName = config('http.structure_name.interpreter', 'http');
        $configName = config('http.structure_name.config', 'config');

        $files = [];

        $interpreterPath = Path::getConfig(
            'interpreter',
            $interpreterName
        );

        if(is_dir($interpreterPath)){
            $files = [
                ...$files,
                ...glob($interpreterPath . '/*' . '.php')
            ];
        }
        if (is_dev()){
            $devConfigPath = path_join($interpreterPath, "dev");
            if (is_dir($devConfigPath)) {
                $files = array_merge($files, glob($devConfigPath . '/*.php'));
            }
        }

        $appPath = Path::getApp(
            $this->app,
            $interpreterName,
            $this->terminal,
            $configName
        );
        if(is_dir($appPath)){
            $files = [
                ...$files,
                ...glob($appPath . '/*' . '.php')
            ];
        }
        if (is_dev()){
            $appDevConfigPath = path_join($appPath, "dev");
            if (is_dir($appDevConfigPath)) {
                $files = [
                    ...$files,
                    ...glob($appDevConfigPath . '/*.php')
                ];
            }
        }
        $configList = load_file_config($files);
        foreach ($configList as $key => $value){
            Config::mount($key, $value);
        }

    }

    protected function getInterfaceClass(string $classNamespace): ReflectionClass
    {
        try {
            $actionClass = $this->getClass($classNamespace);
        } catch (\Throwable $e) {
            throw new ClassNotFoundException($classNamespace, $e);
        }
        return $actionClass;
    }

    protected function initInterface(ReflectionClass $interfaceClass): void
    {
        $this->contentType = $interfaceClass->getProperty('contentType')->getDefaultValue();

    }

    protected function middleware(ReflectionClass $interfaceClass): void
    {
        $this->middlewares = array_unique([
            ...$this->middlewares,
            // 执行应用全局中间件
            ...config('middleware',[]),
            // 执行接口独立中间件
            ...$interfaceClass->getProperty('middleware')->getDefaultValue(),
        ]);

        $this->runMiddleware($this->middlewares, [
            $interfaceClass,
            $this->request,
            $this->app,
            $this->terminal,
            $this->interface,
        ]);

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

    protected function execute(\Closure $callback = null)
    {
        $namespace = $this->getInterfaceNamespace();
        if (!class_exists($namespace)) {
            throw new ClassNotFoundException($namespace);
        }
        $this->initInterpreterConfig();

        $class = $this->getInterfaceClass($namespace);
        $this->initInterface($class);

        $this->middleware($class);

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

    protected function getInterfaceNamespace(): string
    {
        $interpreterName = config('http.structure_name.interpreter', 'http');
        $packageName = config('http.structure_name.package', 'package');
        return join("\\", [
            '',
            'app',
            $this->app,
            $interpreterName,
            $this->terminal,
            $packageName,
            $this->interface
        ]);
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
                $interpreterName = config('http.structure_name.interpreter', 'http');

                $customizedHandle = join("\\",[
                    "",
                    "app",
                    $this->app,
                    $interpreterName,
                    $this->terminal,
                    'HttpExceptionHandle',
                ]);

                $handle = "\\pms\\HttpExceptionHandle";
                if ($inUser && class_exists($customizedHandle)) {
                    $handle = $customizedHandle;
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