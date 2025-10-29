<?php

namespace pms\interpreter\http\sandbox;

use pms\facade\HttpRouter;
use pms\inject\HttpRouteInject;

class HttpRoute implements HttpRouteInject
{

    public string $app;

    public string $terminal;
    public string $terminalMode;

    public string $interface;
    public ?string $interfaceClass = null;

    public bool $stm;


    public function __construct(protected string $pathinfo){
        $this->analysisPathInfo();
    }

    protected function analysisPathInfo(): void
    {
        $pathinfo = $this->pathinfo;
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
                $this->terminal = $arr[0];
                break;
            case 2:
                $this->terminal = $arr[0];
                $this->app = $arr[1];
                break;
            default:
                $this->terminal = $arr[0];
                $this->app = $arr[1];
                $this->interface = join("\\",array_slice($arr, 2));
                break;
        }
        // 检测当前应用是否为STM
        $this->terminalMode = config('http.terminal_mode', 'single');
        if($this->terminalMode === 'multiple'){
            $stm = config('http.stm',[]);
            if(is_string($stm)){
                $stm = [$stm];
            }
            $this->stm = in_array($this->app,$stm);
        }else{
            $this->terminalMode = 'single';
            $mtm = config('http.mtm',[]);
            if(is_string($mtm)){
                $mtm = [$mtm];
            }
            $this->stm = !in_array($this->app,$mtm);
        }

    }

    public function inStatic(): bool
    {
        $static = config('http.static',[]);
        if (is_string($static)) {
            $static = [$static];
        }
        return in_array($this->terminal, $static);
    }

    public function inApp(): bool{
        $apps = config('http.apps',[]);
        if (is_string($apps)) {
            $apps = [$apps];
        }
        $realApp = [];
        foreach ($apps as $key => $value){
            if(is_string($key)){
                $realApp[] = $key;
            }else if(is_string($value)){
                $realApp[] = $value;
            }
        }
        return in_array($this->app, $realApp);
    }

    public function inTerminal(): bool{
        $exclude = config('http.exclude_terminal',[]);
        $current = $this->app . '.' .$this->terminal;
        return !in_array($current, $exclude);
    }

    protected function _loadInterfaceClass(): void
    {
        HttpRouter::load($this->app);
        $namespace = HttpRouter::findClass($this->pathinfo,[
            'app' => $this->app,
            'terminal' => $this->terminal,
        ]);
        if($namespace !== null){
            $this->interfaceClass = $namespace;
            return;
        }
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
        return;
    }


    protected function generateInterfaceNamespace($app, $terminal, $interface): string{
        $packageName = config('http.structure_name.package', 'http');
        if($this->stm){
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
            case 'loadInterfaceClass':
                $this->_loadInterfaceClass();
        }
    }


}