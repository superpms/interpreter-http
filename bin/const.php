<?php

/**
 * 应用模式:多终端模式
 */
const HTTP_APP_MODE_MULTIPLE = 'multiple';

/**
 * 应用模式:单终端端模式
 */
const HTTP_APP_MODE_SINGLE = 'single';

/**
 * 路由模式:app模式
 */
const HTTP_ROUTE_MODE_APP = 'app';

/**
 * 路由模式:终端模式
 */
const HTTP_ROUTE_MODE_TERMINAL = 'terminal';


function HttpCustomErrorHandler($errno, $errstr, $errfile, int $errline){

    if (!(error_reporting() & $errno)) {
        // 这个错误代码未被包含在 error_reporting 中
        return;
    }
    throw new \pms\exception\WarningException($errno, $errstr, $errfile, $errline);
}