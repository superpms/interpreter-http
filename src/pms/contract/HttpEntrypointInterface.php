<?php

namespace pms\contract;

use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;

/** HTTP 挂载处理器，按已加载的宿主配置提供入口路径。 */
interface HttpEntrypointInterface
{
    /** @return list<string> 当前挂载路径 */
    public static function prefixes(): array;

    /** 使用当前传输层的请求和响应执行挂载入口。 */
    public static function handle(HttpRequestInject $request, HttpResponseInject $response): void;
}
