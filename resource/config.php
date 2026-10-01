<?php

/** HTTP 宿主配置，业务应用名单由宿主维护。 */
return [
    'app' => [
        'mode' => HTTP_APP_MODE_MULTIPLE,
        'default' => ['app' => 'index', 'terminal' => 'index', 'interface' => 'Index'],
        'structure' => ['package' => 'http', 'config' => 'config'],
        'route' => [
            'mode' => HTTP_ROUTE_MODE_TERMINAL,
            'file' => 'http_router.php',
            'prefix' => 'api',
        ],
        'provide' => ['index'],
        'special' => [],
        'exclude' => [],
    ],
    // 当前 HttpRoute 使用此字段生成接口命名空间。
    'structure_name' => ['package' => 'http'],
    // PHP 开发服务器目录与解释器静态目录分别读取以下字段。
    'root' => 'public',
    'web_root' => '/public',
    'static' => ['uploads', 'static'],
    'header' => [
        'scheme_name' => ['x-forwarded-scheme'],
        'ip_name' => ['x-real-ip'],
    ],
    'cors' => [
        'Access-Control-Max-Age' => 86400,
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization'],
    ],
    'exception' => [
        'default' => \pms\HttpExceptionHandle::class,
        'app' => 'HttpExceptionHandle',
    ],
];
