<?php

namespace pms\interpreter\http\sandbox;

use pms\exception\SystemException;
use pms\facade\BootOptions;
use pms\facade\HttpRouter;
use pms\HttpExceptionHandle;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;
use pms\inject\HttpRouteInject;
use pms\interpreter\http\Sandbox;
use pms\OptionsAccess;
use pms\program\httpRoute\HttpRouteCoroutine;

class HttpRoute extends OptionsAccess implements HttpRouteInject
{

    protected ?HttpRequestInject $request = null;
    protected ?HttpResponseInject $response = null;

    protected bool $isForward = false;


    public function __construct(string $pathinfo,?string $forward = null){
        parent::__construct(false);
        if($forward !== null){
            $this->isForward = true;
            $this->interfaceClass = $forward;
        }else{
            $this->interfaceClass = null;
        }

        $this->pathinfo = $pathinfo;
        $this->app = config('http.app.default.app', 'index');
        $this->terminal = config('http.app.default.terminal', 'index');
        $this->terminalMode = config('http.app.mode', HTTP_APP_MODE_SINGLE);
        $this->interface = config('http.app.default.interface', 'Index');
        $this->model = config('http.app.route.mode',HTTP_ROUTE_MODE_APP);
        $this->analysisPathInfo();
        $this->useTerminalMode();
        $this->calcInStatic();
        $this->calcInApp();
        $this->calcInTerminal();
    }

    /**
     * 激活
     * @param HttpRequestInject  $request
     * @param HttpResponseInject $response
     * @return void
     */
    public function activate( HttpRequestInject $request, HttpResponseInject $response): void
    {
        $this->request = $request;
        $this->response = $response;
        $params = $this->constructInterface();
        if ($params !== []) {
            $this->request->mergeGet($params);
        }
    }

    /**
     * 异常
     * @return void
     */
    public function exception(){

    }

    protected function analysisPathInfo(): void{
        // 提前过滤无效路径段
        $arr = array_values(array_filter(explode("/", $this->pathinfo), function($value) {
            return $value !== '' && $value !== '.' && $value !== '..';
        }));
        if(count($arr) === 0){
            return;
        }
        if($this->model === HTTP_ROUTE_MODE_TERMINAL){
            $this->terminal = $arr[0];
            if(isset($arr[1])){
                $this->app = $arr[1];
            }
        }else{
            $this->model = HTTP_ROUTE_MODE_APP;
            $this->app = $arr[0];
            if(isset($arr[1])){
                $this->terminal = $arr[1];
            }
        }
        if(count($arr) > 2){
            $this->interface = join("\\",array_slice($arr, 2));
        }
    }

    protected function useTerminalMode(): void
    {
        $special = config('http.app.special',[]);
        if(is_string($special)){
            $special = [$special];
        }
        // 检测当前应用是否为 single
        if($this->terminalMode === HTTP_APP_MODE_MULTIPLE){
            $this->isStm = in_array($this->app,$special);
        }else{
            $this->terminalMode = HTTP_APP_MODE_SINGLE;
            $this->isStm = !in_array($this->app,$special);
        }
    }

    protected function calcInStatic(): void
    {
        $static = config('http.static',[]);
        if (is_string($static)) {
            $static = [$static];
        }
        $this->inStatic = in_array($this->terminal, $static);
    }

    protected function calcInApp(): void{
		$cfg = config('http.app.provide',[]);
        $apps = is_array($cfg) ? $cfg : [$cfg];
        $this->inApp = in_array($this->app, $apps);
    }

    protected function calcInTerminal(): void{
        $exclude = config('http.app.exclude',[]);
        $current = $this->app . '.' .$this->terminal;
        $this->inTerminal = !in_array($current, $exclude);
    }

    public function exceptionClass(){
        $name = config('http.exception.app','HttpExceptionHandle');
        $app = BootOptions::get_dir_app();
        if($this->isStm){
            $customizedHandle = join("\\",[
                "",
                trim($app,'/'),
                $this->app,
                $name,
            ]);
        }else{
            $customizedHandle = join("\\",[
                "",
                trim($app,'/'),
                $this->app,
                $this->terminal,
                $name,
            ]);
        }
        if(!class_exists($customizedHandle)){
            $customizedHandle = config('http.exception.default','');
            if(!class_exists($customizedHandle)){
                $customizedHandle = HttpExceptionHandle::class;
            }
        }
        return $customizedHandle;
    }

    /**
     * 按精确映射、目录接口、动态前缀的顺序定位接口，返回路径参数。
     * 本方法只解析路由，供 HTTP 激活流程与接口元数据共用。
     *
     * @return array<string, string> 动态路径参数
     */
    public function constructInterface(): array
    {
        if ($this->isForward) {
            return [];
        }

        HttpRouter::load($this->app);
        $this->interfaceClass = $this->findInterfaceClass();
        if ($this->interfaceClass !== null) {
            return [];
        }

        $dynamic = HttpRouter::findDynamic($this->pathinfo, [
            'app' => $this->app,
            'terminal' => $this->terminal,
        ]);
        if ($dynamic === null) {
            $this->interfaceClass = $this->generateInterfaceNamespace($this->app, $this->terminal, $this->interface);
            return [];
        }

        $this->pathinfo = $dynamic['path'];
        $this->interface = config('http.app.default.interface', 'Index');
        $this->analysisPathInfo();
        $this->interfaceClass = $this->findInterfaceClass()
            ?? $this->generateInterfaceNamespace($this->app, $this->terminal, $this->interface);
        return $dynamic['params'];
    }

    /**
     * 查找当前完整路径的显式映射与实际存在的目录类。
     * 显式映射选定后，由执行层检查目标类与接口准入。
     *
     * @return string|null 接口类名；没有匹配时返回 null
     */
    protected function findInterfaceClass(): ?string
    {
        $option = ['app' => $this->app, 'terminal' => $this->terminal];
        $class = HttpRouter::findClass($this->pathinfo, $option);
        if ($class !== null) {
            return $class;
        }

        $terminals = HttpRouter::findTerminalAlias($this->app, $this->terminal, $option) ?? [];
        foreach ($terminals as $terminal) {
            $class = $this->generateInterfaceNamespace($this->app, $terminal, $this->interface);
            if (class_exists($class)) {
                return $class;
            }
        }

        $class = $this->generateInterfaceNamespace($this->app, $this->terminal, $this->interface);
        return class_exists($class) ? $class : null;
    }


    protected function generateInterfaceNamespace($app, $terminal, $interface): string{
        $packageName = config('http.structure_name.package', 'http');
        if($this->isStm){
            return join("\\", [
                '',
                'app',
                $app,
                $packageName,
                $interface
            ]);
        }
        return join("\\", [
            '',
            'app',
            $app,
            $terminal,
            $packageName,
            $interface
        ]);
    }




    protected ?HttpRouteCoroutine $ForwardCoroutine = null;

    /**
     * 获取路由协程容器
     * @return HttpRouteCoroutine
     */
    public function getCoroutine(): HttpRouteCoroutine
    {
        if($this->ForwardCoroutine === null){
            $this->ForwardCoroutine = new HttpRouteCoroutine();
        }
        return $this->ForwardCoroutine;
    }

    /**
     * 路由转发
     * @param string $forwardClass 转发目标类名
     * @param array|null $params 独立转发参数；null 时共享当前请求
     * @param string|null $method 转发请求方法
     * @param string|null $pathinfo 转发目标路径
     * @return HttpRouteCoroutine
     * @throws \Throwable 转发异常交由调用入口处理
     */
    public function forward(
        string $forwardClass,
        ?array $params = null,
        ?string $method = null,
        ?string $pathinfo = null,
    ): HttpRouteCoroutine
    {
        if($this->request === null || $this->response === null){
            throw new SystemException('当前路由未激活,无法使用路由转发');
        }
        $request = $params === null && $method === null && $pathinfo === null
            ? $this->request
            : $this->request->withParams($params ?? $this->request->params(), $method, $pathinfo);
        $forwardCoroutine = $this->getCoroutine();
        $forwardCoroutine->pushResult(
            $forwardClass,
            (new Sandbox($request, $this->response))->run($forwardClass)
        );
        return $forwardCoroutine;
    }



}
