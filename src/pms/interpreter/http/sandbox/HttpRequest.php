<?php

namespace pms\interpreter\http\sandbox;

use pms\inject\HttpRequestInject;
use Throwable;

class HttpRequest implements HttpRequestInject
{
	protected array $server = [];
	protected array $header = [];
	protected bool $isHttps;
	protected string $ip;
	protected string $scheme;
	protected string $host;
	protected string $pathinfo;
	protected array $get = [];
	protected array $post = [];
	protected string $input = "";
	protected bool $inputLoaded = false;
	protected array $files = [];
	protected array $cookie = [];
	protected array $params = [];
	protected string $method;
	protected string $contentType;
	protected array $attach = [];
	
	public function server(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->server;
		}
		return $this->server[strtoupper($name)] ?? $this->server[$name] ?? $this->server[strtolower($name)] ?? $default;
	}
	
	public function header(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->header;
		}
		return $this->header[$name] ?? $this->header[strtolower($name)] ?? $this->header[strtoupper($name)] ?? $default;
	}
	
	public function params(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->params;
		}
		return $this->params[$name] ?? $default;
	}
	
	public function cookie(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->cookie;
		}
		return $this->cookie[$name] ?? $default;
	}
	
	public function files(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->files;
		}
		return $this->files[$name] ?? $default;
	}
	
	public function post(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->post;
		}
		return $this->post[$name] ?? $default;
	}
	
	public function get(?string $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->get;
		}
		return $this->get[$name] ?? $default;
	}
	
	public function input(): string
	{
		$content = $this->getContent();
		return $content === false ? "" : $content;
	}

	public function getContent(): string|false
	{
		if (!$this->inputLoaded) {
			$content = file_get_contents("php://input");
			if ($content === false) {
				return false;
			}
			$this->input = $content;
			$this->inputLoaded = true;
		}
		return $this->input;
	}

	public function rawContent(): string|false
	{
		return $this->getContent();
	}

	public function getData(): string|false
	{
		return false;
	}

	public function getMethod(): string|false
	{
		return $this->method();
	}

	public function parse(string $data): int|false
	{
		return false;
	}

	public function isCompleted(): bool
	{
		return true;
	}
	
	public function contentType(): string
	{
		return $this->contentType;
	}
	
	public function ip(): string
	{
		return $this->ip;
	}
	
	public function scheme(): string
	{
		return $this->scheme;
	}
	
	public function host(): string
	{
		return $this->host;
	}
	
	public function domain(): string
	{
		return $this->scheme() . '://' . $this->host();
	}
	
	public function builder(string|array $path = ""): string
	{
		if (is_string($path)) {
			$path = [$path];
		}
		$path = implode('/', $path);
		$path = str_replace('\\', '/', $path);
		$path = str_replace('//', '/', $path);
		$path = ltrim($path, "/");
		return $this->domain() . "/" . $path;
	}
	
	public function pathinfo(): string
	{
		return $this->pathinfo;
	}
	
	public function isHttps(): bool
	{
		return $this->isHttps;
	}
	
	public function isAjax(): bool
	{
		return strtoupper((string)$this->header('x-requested-with', '')) === 'XMLHTTPREQUEST';
	}
	
	public function isPjax(): bool
	{
		return strtoupper((string)$this->header('x-pjax', '')) === 'TRUE';
	}
	
	public function isPost(): bool
	{
		return $this->method() === 'POST';
	}
	
	public function isGet(): bool
	{
		return $this->method() === 'GET';
	}
	
	public function isPut(): bool
	{
		return $this->method() === 'PUT';
	}
	
	public function isDelete(): bool
	{
		return $this->method() === 'DELETE';
	}
	
	public function isHead(): bool
	{
		return $this->method() === 'HEAD';
	}
	
	public function isOptions(): bool
	{
		return $this->method() === 'OPTIONS';
	}
	
	public function method(): string
	{
		return $this->method;
	}
	
	public function setAttach(string $key, mixed $data): void
	{
		$this->attach[$key] = $data;
	}
	
	public function getAttach(string $key, mixed $default = null): mixed
	{
		return $this->attach[$key] ?? $default;
	}
	
	protected function getIsHttps(): bool
	{
		$schemeName = config('http.header.scheme_name', 'x-forwarded-scheme');
		if (is_string($schemeName)) {
			$schemeName = [$schemeName];
		}
		$isHttps = false;
		foreach ($schemeName as $value) {
			$scheme = strtolower(trim(explode(',', (string)$this->header(strtolower($value), ''), 2)[0]));
			if ($scheme === 'https') {
				$isHttps = true;
				break;
			}
		}
		if (!$isHttps && strtolower(trim(explode(',', (string)$this->header('x-forwarded-proto', ''), 2)[0])) === 'https') {
			$isHttps = true;
		}
		if (!$isHttps && strtoupper((string)$this->server('https', 'off')) === 'ON') {
			$isHttps = true;
		}
		if (!$isHttps && (string)$this->server('server_port', '') === '443') {
			$isHttps = true;
		}
		return $isHttps;
	}
	
	protected function getIp(): string
	{
		$ipName = config('http.header.ip_name', 'x-real-ip');
		if (is_string($ipName)) {
			$ipName = [$ipName];
		}
		$ip = '';
		foreach ($ipName as $value) {
			$t = (string)$this->header(strtolower($value), '');
			if ($t !== '') {
				$ip = $t;
				break;
			}
		}
		if ($ip === '') {
			$ip = (string)$this->server('remote_addr', '');
		}
		return $ip;
	}
	
	final public function __construct(...$args)
	{
		$this->platformConstruct(...$args);
		$this->method = strtoupper((string)$this->server('request_method', 'GET'));
		$pathinfo = (string)$this->server('request_uri', '');
		if (empty($pathinfo)) {
			$pathinfo = "/";
		}
		if (str_contains($pathinfo, "?")) {
			$pathinfo = substr($pathinfo, 0, strpos($pathinfo, "?"));
		}
		$this->pathinfo = $pathinfo;
	}
	
	protected function platformConstruct(...$args): void
	{
		$this->server = [];
		foreach ($_SERVER as $key => $value) {
			$this->server[strtolower($key)] = $value;
		}
		$this->normalizeServerForSwoole();
		$headers = [];
		if (function_exists('getallheaders') && getallheaders() !== false) {
			foreach (getallheaders() as $key => $value) {
				$headerName = str_replace(' ', '-', str_replace('_', ' ', strtolower($key)));
				$headers[$headerName] = $value;
			}
		} else {
			foreach ($_SERVER as $key => $value) {
				if (str_starts_with($key, 'HTTP_')) {
					$headerName = str_replace(' ', '-', str_replace('_', ' ', strtolower(substr($key, 5))));
					$headers[$headerName] = $value;
				}
			}
		}
		ksort($headers);
		$this->header = $headers;
	}
	
	
	final public function init(): void
	{
		$this->platformInit();
		$this->isHttps = $this->getIsHttps();
		$this->ip = $this->getIp();
		$this->host = (string)$this->header('host', $this->server('http_host', $this->server('server_name', '')));
		$this->scheme = $this->isHttps ? "https" : "http";
		$this->contentType = (string)$this->header('content-type', 'text/plain');
		$contentType = strtolower(trim(explode(';', $this->contentType, 2)[0]));
		if ($contentType === 'application/json' && $this->input !== "") {
			try {
				$jsonPost = json_decode($this->input, true);
				$this->post = [
					...$this->post,
					...(is_array($jsonPost) ? $jsonPost : [])
				];
			} catch (Throwable $e) {
				$this->post = [
					...$this->post,
				];
			}
		}
		$this->params = array_merge($this->get, $this->post, $this->files);
	}
	
	protected function platformInit(): void
	{
		ksort($this->server);
		$this->cookie = $_COOKIE;
		$this->get = $_GET;
		$this->post = $_POST;
		$this->files = $_FILES;
		$content = $this->getContent();
		$this->input = $content === false ? "" : $content;
	}

	protected function normalizeServerForSwoole(): void
	{
		$requestUri = (string)($this->server['request_uri'] ?? '');
		if ($requestUri === '') {
			return;
		}
		$queryPosition = strpos($requestUri, '?');
		if ($queryPosition !== false) {
			$this->server['query_string'] ??= substr($requestUri, $queryPosition + 1);
			$requestUri = substr($requestUri, 0, $queryPosition);
			$this->server['request_uri'] = $requestUri;
		}
		$this->server['path_info'] ??= $requestUri;
	}
}
