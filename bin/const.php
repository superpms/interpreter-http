<?php

/**
 * 应用模式:多终端模式
 */
const PMS_HTTP_APP_MODE_MULTIPLE = 'multiple';

/**
 * 应用模式:单终端端模式
 */
const PMS_HTTP_APP_MODE_SINGLE = 'single';

/**
 * 路由模式:app模式
 */
const PMS_HTTP_ROUTE_MODE_APP = 'app';

/**
 * 路由模式:终端模式
 */
const PMS_HTTP_ROUTE_MODE_TERMINAL = 'terminal';


function HttpCustomErrorHandler($errno, $errstr, $errfile, int $errline){
    throw new \pms\exception\WarningException($errno, $errstr, $errfile, $errline);
}