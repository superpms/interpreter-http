<?php

namespace pms\interpreter\http;
use pms\app\InterpreterApp;
use pms\facade\Path;
use pms\interpreter\http\sandbox\HttpRequest;
use pms\interpreter\http\sandbox\HttpResponse;

class Interpreter extends InterpreterApp{

    protected static string $name = 'http-web server';

    public static function run(\pms\program\boot\Options $bootOptions): bool
    {
        Path::mount('WebRoot', Path::getRoot(config('http.structure_name.web_root','public')));
        $request = new HttpRequest();
        $response = new HttpResponse();
        self::customShutDownHandler($response);
        return (new Sandbox($request,$response))->run();
    }

    public static function customShutDownHandler($response): void{
        register_shutdown_function(function ()use($response) {
            $error = error_get_last();
            if (!empty($error)) {
                ob_end_clean();
                $response->status(500, 'Server Error');
                if (config('app.debug')) {
                    $response->header("content-type", JSON_CONTENT_TYPE);
                    $response->end(json_encode($error));
                } else {
                    $response->end();
                }
            }
        });
    }
}