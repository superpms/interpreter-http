<?php

namespace pms\inject;

use pms\program\httpRoute\HttpRouteCoroutine;

/**
 * 路由
 * @property string $pathinfo
 * @property string $app
 * @property string $terminal
 * @property string $terminalMode
 * @property string $interface
 * @property string $interfaceClass
 * @property string $model
 * @property bool   $isStm
 * @property bool   $inPrefix 外部请求前缀准入状态
 * @property bool   $inStatic
 * @property bool   $inApp
 * @property bool   $inTerminal
 */
interface HttpRouteInject
{

    /**
     * 获取接口所属的 HTTP 配置目录。
     * @return list<string> 配置目录
     */
    public function configPaths(): array;

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
    ): HttpRouteCoroutine;

    /**
     * 获取路由协程容器
     * @return HttpRouteCoroutine
     */
    public function getCoroutine(): HttpRouteCoroutine;
}