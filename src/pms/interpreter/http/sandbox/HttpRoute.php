<?php

namespace pms\interpreter\http\sandbox;

use pms\facade\HttpRouter;
use pms\HttpExceptionHandle;
use pms\inject\HttpRouteInject;
use pms\OptionsAccess;
use pms\program\boot\Options;

class HttpRoute extends OptionsAccess implements HttpRouteInject
{


    public function __construct(string $pathinfo,protected Options $bootOptions){
        parent::__construct();
        $this->interfaceClass = null;
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
     * @return void
     */
    public function activate(): void
    {
        $this->constructInterface();
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
        if($this->isStm){
            $customizedHandle = join("\\",[
                "",
                trim($this->bootOptions->dir_app,'/'),
                $this->app,
                $name,
            ]);
        }else{
            $customizedHandle = join("\\",[
                "",
                trim($this->bootOptions->dir_app,'/'),
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

    protected function constructInterface(): void
    {
        HttpRouter::load($this->app);
		$this->interfaceClass = HttpRouter::findClass($this->pathinfo,[
            'app' => $this->app,
            'terminal' => $this->terminal,
        ]);
        if($this->interfaceClass === null){
			$namespaceTerminal = HttpRouter::findTerminalAlias($this->app,$this->terminal,[
				'app' => $this->app,
				'terminal' => $this->terminal,
			]);
			if(!empty($namespaceTerminal)){
				foreach ($namespaceTerminal as $terminal){
					$namespace = $this->generateInterfaceNamespace($this->app, $terminal, $this->interface);
					if(class_exists($namespace)){
						$this->interfaceClass = $namespace;
						return;
					}
				}
			}
			$namespace = $this->generateInterfaceNamespace($this->app, $this->terminal, $this->interface);
			$this->interfaceClass = $namespace;
        }
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


    public function __call(string $name, array $arguments){
        switch ($name){
            case 'constructInterface':
                $this->_constructInterface();
        }
    }


}