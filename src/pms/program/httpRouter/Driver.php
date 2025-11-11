<?php

namespace pms\program\httpRouter;

use pms\facade\Path;

/**
 * 路由驱动类
 * 负责管理路由路径与处理类的映射关系
 */
class Driver
{

    protected string $TEMPLATE_TERMINAL = '{$terminal}';

    /**
     * 使用动态终端模板
     * @return string
     */
    public function useTerminalTemplate(): string
    {
        return $this->TEMPLATE_TERMINAL;
    }

    protected string $TEMPLATE_APP = '{$app}';

    /**
     * 使用动态APP模板
     * @return string
     */
    public function useAppTemplate(): string
    {
        return $this->TEMPLATE_APP;
    }

    /**
     * 路由容器，存储路径与类的映射关系
     * @var array
     */
    protected array $container = [];

    /**
     * 路由别名容器，存储终端原名与别名映射关系
     */
    protected array $terminalAliasContainer = [];

    /**
     * 配置数组，用于记录已加载的应用配置
     * @var array
     */
    protected array $config = [];

    /**
     * 添加路由映射关系
     * 将指定路径映射到对应的处理类
     * @param string $path 路由路径
     * @param string $class 处理类名
     * @return static 返回当前实例，支持链式调用
     */
    public function pusher(string $path, string $class): static
    {
        $this->container[$path] = $class;
        return $this;
    }


    /**
     * 为指定类绑定多个路由路径
     * 支持将一个处理类绑定到多个路径
     * @param string $class 处理类名
     * @param array|string $paths 路由路径，可以是字符串或数组
     * @return static 返回当前实例，支持链式调用
     */
    public function provider(string $class, array|string $paths): static
    {
        if (is_string($paths)) {
            $paths = [$paths];
        }
        foreach ($paths as $path) {
            $this->pusher($path, $class);
        }
        return $this;
    }


    /**
     * 加载应用的路由配置文件
     * 如果指定应用的路由配置未加载，则加载对应的router.php文件
     * @param string $app 应用名称
     * @return void
     */
    protected function _load(string $app): void
    {
        if (!array_key_exists($app, $this->config)) {
            $fName = config('http.app.route.file', 'http_router.php');
            $path = Path::getApp($app, $fName);
            if (file_exists($path)) {
                require_once $path;
            }
            $this->config[$app] = true;
        }
    }

    /**
     * 设置终端别名
     * @param string $app
     * @param string $terminalName
     * @param string|array $aliasTerminalName
     * @return void
     */
    public function terminalAlias(string $app, string $terminalName, string|array $aliasTerminalName): void
    {
        if (is_string($aliasTerminalName)) {
            $aliasTerminalName = [$aliasTerminalName];
        }
        foreach ($aliasTerminalName as $item) {
            $key = $app . '/' . $item;
            if (!isset($this->terminalAliasContainer[$key])) {
                $this->terminalAliasContainer[$key] = [];
            }
            $this->terminalAliasContainer[$key][] = $terminalName;
        }
    }

    protected function _findTerminalAlias($currentApp, $currentTerminal, $option = []): ?array
    {
		$current = $currentApp . '/' . $currentTerminal;
        if (!empty($option) && !isset($this->terminalAliasContainer[$current])) {
            foreach ($this->terminalAliasContainer as $alias => $terminalList) {
                foreach ($option as $key => $value) {
                    $templateFieldKey = strtoupper('TEMPLATE_' . $key);
                    $template = $this->{$templateFieldKey};
                    $alias = str_replace($template, $value, $alias);
                }
                $this->terminalAliasContainer[$alias] = $terminalList;
            }
        }
        if (!isset($this->terminalAliasContainer[$current])) {
            return null;
        }
        $currentAlias = $this->terminalAliasContainer[$current] ?? null;
        if (empty($currentAlias)) {
            return null;
        }
        return array_reverse($currentAlias);
    }


    /**
     * 查找指定路径对应的处理类
     *
     * @param string $path 路由路径
     * @param array $option 路由参数
     * @return string|null 返回对应的处理类名，如果未找到则返回null
     */
    protected function _findClass(string $path, array $option = []): ?string
    {
        if (!empty($option) && !array_key_exists($path, $this->container)) {
            foreach ($this->container as $router => $item) {
                foreach ($option as $key => $value) {
                    $templateFieldKey = strtoupper('TEMPLATE_' . $key);
                    $template = $this->{$templateFieldKey};
                    $router = str_replace($template, $value, $router);
                }
                $this->container[$router] = $item;
            }
        }
        return $this->container[$path] ?? null;
    }

    // 隐藏框架内部方法
    public function __call(string $name, array $arguments)
    {
        switch ($name) {
            case 'load':
                $this->_load(...$arguments);
                break;
            case 'findClass':
                return $this->_findClass(...$arguments);
            case 'findTerminalAlias':
                return $this->_findTerminalAlias(...$arguments);
        }
    }

}