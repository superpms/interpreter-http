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
	protected array $files = [];
	protected array $cookie = [];
	protected array $params = [];
	protected string $method;
	protected string $contentType;
	protected array $attach = [];
	
	public function server(?string $name = null, mixed $default = null): array|string|null
	{
		if ($name === null) {
			return $this->server;
		}
		return $this->server[strtoupper($name)] ?? $this->server[$name] ?? $this->server[strtolower($name)] ?? $default;
	}
	
	public function header(?string $name = null, mixed $default = null): array|string|null
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
	
	public function cookie(?string $name = null, mixed $default = null): array|string|null
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
	
	public function post(?string $name = null, mixed $default = null): array|string|null
	{
		if ($name === null) {
			return $this->post;
		}
		return $this->post[$name] ?? $default;
	}
	
	public function get(?string $name = null, mixed $default = null): array|string|null
	{
		if ($name === null) {
			return $this->get;
		}
		return $this->get[$name] ?? $default;
	}
	
	public function input(): string
	{
		return $this->input;
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
		return strtoupper($this->header('x-requested-with')) === strtoupper('XMLHttpRequest');
	}
	
	public function isPjax(): bool
	{
		return strtoupper($this->header('x-pjax')) === 'TRUE';
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
			if ($this->header(strtolower($value)) === 'https') {
				$isHttps = true;
				break;
			}
		}
		if (!$isHttps && strtoupper($this->server('https', 'off')) === 'ON') {
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
			$t = $this->header(strtolower($value), '');
			if ($t !== '') {
				$ip = $t;
				break;
			}
		}
		if ($ip === '') {
			$ip = $this->server('remote_addr');
		}
		return $ip;
	}
	
	final public function __construct(...$args)
	{
		$this->platformConstruct(...$args);
		$this->method = strtoupper($this->server('request_method'));
		$pathinfo = $this->server('request_uri');
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
		$this->server = $_SERVER;
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
		$this->host = $this->header('host');
		$this->scheme = $this->isHttps ? "https" : "http";
		$this->contentType = $this->header('content-type', 'text/plain');
		if (strtolower($this->contentType) === 'application/json' && $this->input !== "") {
			try {
				$this->post = [
					...$this->post,
					...json_decode($this->input, true)
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
		$this->input = file_get_contents("php://input");
	}
}