<?php

namespace pms\interpreter\http;

use pms\app\InterpreterApp;
use pms\contract\HttpEntrypointInterface;
use pms\hook\HttpEntrypointHook;
use pms\inject\HttpResponseInject;
use pms\broadcast\SystemErrorBroadcast;
use pms\facade\BootOptions;
use pms\facade\Ctx;
use pms\facade\Path;
use pms\hook\HttpLifecycleHook;
use pms\inject\HttpRequestInject;
use pms\interpreter\http\sandbox\HttpRequest;
use pms\interpreter\http\sandbox\HttpResponse;

class Interpreter extends InterpreterApp implements HttpEntrypointInterface
{

    protected static string $name = 'http-web server';

    public static function entry(): bool
    {
        Ctx::set(HttpRequestInject::class, null);
        $request = new HttpRequest();
        $response = new HttpResponse();
        SystemErrorBroadcast::listener(function () use ($response) {
            static::handleBasicError($response);
        },true);
        HttpLifecycleHook::run(LIFECYCLE_BOOT);
        self::customShutDownHandler($response);
        Path::mount('WebRoot', Path::getRoot(config('http.web_root', '/public')));
        HttpLifecycleHook::run(LIFECYCLE_BOOTED);
        HttpLifecycleHook::run(LIFECYCLE_SERVER_BOOTED);
        self::dispatch($request, $response);
        return true;
    }

    /** HTTP 业务入口使用宿主配置的公共前缀。 */
    public static function prefixes(): array
    {
        return [HttpEntrypointHook::normalizePrefix(config('http.app.route.prefix', ''))];
    }

    /** 普通 HTTP 与 Swoole 的公共请求分发入口。 */
    public static function dispatch(HttpRequestInject $request, HttpResponseInject $response): void
    {
        if (!HttpEntrypointHook::run($request, $response)) {
            self::handle($request, $response);
        }
    }

    /** 执行 HTTP 业务、静态资源及未命中入口的 404 处理。 */
    public static function handle(HttpRequestInject $request, HttpResponseInject $response): void
    {
        (new Sandbox($request, $response))->run();
    }

    public static function handleBasicError(HttpResponse $response)
    {
        $basicError = pms_error();
        if ($basicError === null) {
            return;
        }
        pms_error_clear();
        $response->setHeader('content-type', JSON_CONTENT_TYPE);
        $debug = BootOptions::get_error_debug();
        if ($debug === true) {
            $response->end(json_encode([
                'message' => $basicError->getMessage(),
                'code' => 500,
                'error' => $basicError->getCode(),
                'file' => $basicError->getFile(),
                'line' => $basicError->getLine(),
                'trace' => $basicError->getTrace(),
            ]));
        } else {
            $response->end(json_encode([
                'message' => '系统内部错误',
                'code' => 500
            ]));
        }
    }

    public static function customShutDownHandler(HttpResponse $response): void
    {
        register_shutdown_function(function () use ($response) {
            $error = error_get_last();
            if (!empty($error)) {
                ob_end_clean();
                $response->status(500, 'Server Error');
                $response->header("content-type", JSON_CONTENT_TYPE);
                if (BootOptions::get_error_debug()) {
                    $response->end(json_encode([
                        'error' => $error,
                        'type' => 'shutdown',
                        'code' => 500,
                        'message' => '系统内部错误',
                    ]));
                } else {
                    $response->end(json_encode([
                        'message' => '系统内部错误',
                        'code' => 500
                    ]));
                }
            }
        });
    }
}
