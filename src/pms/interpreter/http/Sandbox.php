<?php

namespace pms\interpreter\http;

use pms\app\HttpApp;
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
use pms\inject\HttpRouteInject;
use pms\interpreter\http\sandbox\HttpRoute;
use pms\program\boot\Options;
use ReflectionClass;


class Sandbox extends Container
{
    protected HttpRequestInject $request;
    protected HttpResponseInject $response;
    protected HttpRouteInject $route;
    protected Options $bootOptions;
    protected array $middlewares = [];
    protected string $contentType = JSON_CONTENT_TYPE;


    public function __construct(HttpRequestInject $request, HttpResponseInject $response,Options $bootOptions){
        $this->request = $request;
        $this->response = $response;
        $this->bootOptions = $bootOptions;
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

            $this->route = new HttpRoute($this->request->pathinfo());

            if($this->route->inStatic()){
                $this->sendFile($this->request->pathinfo());
                return true;
            }

            if (!$this->route->inApp()) {
                $this->response->status(500,"Gateway Not Found");
                $this->response->end('Gateway Not Found');
                return true;
            }

            if (!$this->route->inTerminal()) {
                $this->response->status(500,"Gateway Not Found");
                $this->response->end('Gateway Not Found');
                return true;
            }
            $this->request->init();

            $this->putInject();

            $this->route->loadInterfaceClass();

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


//    protected function analysisPathInfo(): void
//    {
//        $pathinfo = $this->pathinfo;
//        $arr = explode("/",$pathinfo);
//        $this->app = config('http.default.app', 'index');
//        $this->terminal = config('http.default.terminal', 'index');
//        $this->interface = config('http.default.interface', 'Index');
//        foreach ($arr as $key => $value){
//            if ($value == '' || $value == '.' || $value == '..') {
//                unset($arr[$key]);
//            }
//        }
//        $arr = array_values($arr);
//        switch (count($arr)){
//            case 0:
//                break;
//            case 1:
//                $this->terminal = $arr[0];
//                break;
//            case 2:
//                $this->terminal = $arr[0];
//                $this->app = $arr[1];
//                break;
//            default:
//                $this->terminal = $arr[0];
//                $this->app = $arr[1];
//                $this->interface = join("\\",array_slice($arr, 2));
//                break;
//        }
//
//
//        // 检测当前应用是否为STM
//        $this->terminalMode = config('http.terminal_mode', 'single');
//        if($this->terminalMode === 'multiple'){
//            $stm = config('http.stm',[]);
//            if(is_string($stm)){
//                $stm = [$stm];
//            }
//            $this->stm = in_array($this->app,$stm);
//        }else{
//            $mtm = config('http.mtm',[]);
//            if(is_string($mtm)){
//                $mtm = [$mtm];
//            }
//            $this->stm = !in_array($this->app,$mtm);
//        }
//
//    }





    protected function initCors(): void{
        $responseHeader = config('http.cors', []);
        foreach ($responseHeader as $key => $value) {
            if(is_array($value)){
                $value = join(',',$value);
            }
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
        $this->put(HttpRouteInject::class, $this->route);
    }


    protected function initInterpreterConfig(): void{
        $interpreterName = config('http.structure_name.package', 'http');
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

        // TODO:后续优化下方代码(处理config 模块功能分散问题)
        $appPath = Path::getApp(
            $this->route->app,
            $this->route->terminal,
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
            $this->route->app,
            $this->route->terminal,
            $this->route->interface,
            $this->bootOptions
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
        if (!class_exists($this->route->interfaceClass)) {
            throw new ClassNotFoundException($this->route->interfaceClass);
        }
        $class = $this->getInterfaceClass($this->route->interfaceClass);
        if(!$class->isSubclassOf(HttpApp::class)){
            throw new ClassNotFoundException($this->route->interfaceClass);
        }
        $this->initInterpreterConfig();

        $this->initInterface($class);

        $this->middleware($class);
        /**
         * @var $obj HttpApp
         */
        $obj = $this->invokeClass($class);
        $obj->app = $this->route->app;
        $obj->terminal = $this->route->terminal;
        $obj->bootOptions = $this->bootOptions;

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


    /**
     * 加载异常处理器
     * @param \Throwable $e
     * @param bool $inUser 是否使用应用内客制化处理器
     * @return void
     */
    protected function exceptionHandle(\Throwable $e, bool $inUser = true): void{
        try {
            if (!($e instanceof CliModeForcedInterruptException)) {
                $name = config('http.customized_exception_handle_name','HttpExceptionHandle');
                if($this->route->stm){
                    $customizedHandle = join("\\",[
                        "",
                        trim($this->bootOptions->dir_app,'/'),
                        $this->route->app,
                        $name,
                    ]);
                }else{
                    $customizedHandle = join("\\",[
                        "",
                        trim($this->bootOptions->dir_app,'/'),
                        $this->route->app,
                        $this->route->terminal,
                        $name,
                    ]);
                }

                $handle = "\\pms\\HttpExceptionHandle";
                if($inUser){
                    if (class_exists($customizedHandle)) {
                        $handle = $customizedHandle;
                    }else{
                        $customizedHandle = config('http.exception_handle');
                        if(class_exists($customizedHandle)){
                            $handle = $customizedHandle;
                        }
                    }
                }
                $class = $this->getClass($handle);
                /**
                 * @var HttpExceptionHandle $obj
                 */
                $obj = $this->invokeClass($class, [
                    $this->bootOptions->error_debug,
                    $e,
                    function ($code) {
                        $this->response->status($code);
                    }
                ]);


                $content = $obj->getContent();
                $data = $this->contentToString($content, $this->contentType);
                if($data === false){
                    unset($content['trace']);
                    $data = $this->contentToString($content, $this->contentType);
                }
                $this->response->end($data);
            } else {
                $this->response->setStatusCode(500);
                $this->response->end('');
            }
        } catch (\Throwable $e) {
            // 如果客制化Handle异常，则抛出系统的异常
            $this->exceptionHandle($e, false);
        }

    }

}