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
 * @property bool   $inStatic
 * @property bool   $inApp
 * @property bool   $inTerminal
 */
interface HttpRouteInject
{

    /**
     * 路由转发
     * @param string $forwardClass 转发目标类名
     * @return HttpRouteCoroutine
     */
    public function forward(string $forwardClass): HttpRouteCoroutine;

    /**
     * 获取路由协程容器
     * @return HttpRouteCoroutine
     */
    public function getCoroutine(): HttpRouteCoroutine;
}