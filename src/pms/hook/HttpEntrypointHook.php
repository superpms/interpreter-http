<?php

namespace pms\hook;

use InvalidArgumentException;
use pms\contract\HookAppInterface;
use pms\contract\HttpEntrypointInterface;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;

/** 普通 HTTP 与 Swoole 共用的挂载入口分发。 */
class HttpEntrypointHook implements HookAppInterface
{
    protected static array $container = [];

    /** 注册挂载处理器；路径在宿主配置加载后读取。 */
    public static function mount(string $handler): bool
    {
        if (!is_subclass_of($handler, HttpEntrypointInterface::class)) {
            throw new InvalidArgumentException('HTTP 入口需要实现 HttpEntrypointInterface');
        }
        if (isset(self::$container[$handler])) {
            return false;
        }
        self::$container[$handler] = $handler;
        return true;
    }

    /** 生成以斜线开头的固定挂载路径，空配置表示根路径。 */
    public static function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, '/');
        foreach ($prefix === '' ? [] : explode('/', $prefix) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..'
                || strpbrk($segment, "{}?#\\\0\r\n") !== false) {
                throw new InvalidArgumentException('HTTP 挂载路径需要有效的固定路径段');
            }
        }
        return '/' . $prefix;
    }

    /** 按完整路径段匹配挂载点，返回挂载点之后的路径。 */
    public static function relativePath(string $path, string $prefix): ?string
    {
        if ($prefix === '/') {
            return $path;
        }
        if ($path === $prefix) {
            return '/';
        }
        return str_starts_with($path, $prefix . '/') ? substr($path, strlen($prefix)) : null;
    }

    /**
     * 获取完整请求路径所属的最长匹配挂载处理器。
     * @param string $path 完整路径
     * @return class-string<HttpEntrypointInterface>|null 挂载处理器
     */
    public static function match(string $path): ?string
    {
        foreach (self::audit() as $prefix => $handler) {
            if (self::relativePath($path, $prefix) !== null) {
                return $handler;
            }
        }
        return null;
    }

    /**
     * 将请求交给最长匹配挂载点的处理器。
     * @param HttpRequestInject $request 请求
     * @param HttpResponseInject $response 响应
     * @return bool 是否命中挂载
     */
    public static function run(HttpRequestInject $request, HttpResponseInject $response): bool
    {
        $handler = self::match($request->pathinfo());
        if ($handler === null) {
            return false;
        }
        $handler::handle($request, $response);
        return true;
    }

    /** @return array<string, class-string<HttpEntrypointInterface>> 当前配置的挂载表 */
    public static function audit(): array
    {
        $entries = [];
        foreach (self::$container as $handler) {
            foreach ($handler::prefixes() as $prefix) {
                $prefix = self::normalizePrefix($prefix);
                if (isset($entries[$prefix]) && $entries[$prefix] !== $handler) {
                    throw new InvalidArgumentException('HTTP 挂载路径重复：' . $prefix);
                }
                $entries[$prefix] = $handler;
            }
        }
        uksort($entries, static fn($a, $b) => strlen($b) <=> strlen($a));
        return $entries;
    }
}
