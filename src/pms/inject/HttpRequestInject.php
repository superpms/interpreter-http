<?php

namespace pms\inject;

interface HttpRequestInject {

    public function init(): void;

    /** 注入可信路径参数，保留原始请求体，同名输入以路径参数为准。 */
    public function mergeRouteParams(array $data): void;

    public function server(?string $name = null, mixed $default = null): mixed;

    public function header(?string $name = null, mixed $default = null): mixed;

    public function params(?string $name = null, mixed $default = null): mixed;

    public function cookie(?string $name = null, mixed $default = null): mixed;

    public function files(?string $name = null, mixed $default = null): mixed;

    public function post(?string $name = null, mixed $default = null): mixed;
    public function get(?string $name = null, mixed $default = null): mixed;
    public function mergeGet(array $data): void;

    /** 复制当前请求，替换转发参数及指定的方法、路径，保留已建立的请求上下文。 */
    public function withParams(array $params, ?string $method = null, ?string $pathinfo = null): static;
    public function input(): string;
    public function contentType(): string;
    public function ip(): string;
    public function scheme(): string;
    public function host(): string;
	public function domain(): string;
	/** 生成完整请求地址；true 使用业务前缀，false 使用根路径，字符串指定挂载前缀。 */
	public function builder(string|array $path="", bool|string $prefix = true): string;
	public function pathinfo(): string;
	public function getContent(): string|false;
	public function rawContent(): string|false;
	public function getData(): string|false;
	public function getMethod(): string|false;
	public function parse(string $data): int|false;
	public function isCompleted(): bool;
	public function isHttps(): bool;
    public function isAjax(): bool;
    public function isPjax(): bool;
    public function isPost(): bool;
    public function isGet(): bool;
    public function isPut(): bool;
    public function isDelete(): bool;
    public function isHead(): bool;
    public function isOptions(): bool;
    public function method(): string;
    public function setAttach(string $key,mixed $data):void;
    public function getAttach(string $key, mixed $default = null):mixed;
}
