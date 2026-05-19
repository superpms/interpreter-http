<?php

namespace pms\interpreter\http\sandbox;


use pms\inject\HttpResponseInject;
use Throwable;

class HttpResponse implements HttpResponseInject
{

    public function __construct(){}

    protected bool $end = false;
    protected bool $detach = false;
    protected array $headers = [];
    protected array $cookies = [];
    protected array $trailers = [];

    public function isWritable(): bool
    {
        return !$this->end && !$this->detach;
    }

    public function initHeader(): bool
    {
        return $this->isWritable();
    }

    public function cookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = '', bool $partitioned = false): bool
    {
        return $this->sendCookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority, $partitioned, true);
    }

    public function setCookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = '', bool $partitioned = false): bool
    {
        return $this->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority, $partitioned);
    }

    public function rawcookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = '', bool $partitioned = false): bool
    {
        return $this->sendCookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority, $partitioned, false);
    }

    public function setRawCookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = '', bool $partitioned = false): bool
    {
        return $this->rawcookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority, $partitioned);
    }

    protected function sendCookie(string $name, string $value, int $expires, string $path, string $domain, bool $secure, bool $httponly, string $samesite, string $priority, bool $partitioned, bool $encode): bool
    {
        if (!$this->isWritable() || headers_sent()) {
            return false;
        }
        $cookie = $this->buildCookieHeader($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority, $partitioned, $encode);
        if ($cookie === false) {
            return false;
        }
        $this->cookies[] = $cookie;
        header('Set-Cookie: ' . $cookie, false);
        return true;
    }

    protected function buildCookieHeader(string $name, string $value, int $expires, string $path, string $domain, bool $secure, bool $httponly, string $samesite, string $priority, bool $partitioned, bool $encode): string|false
    {
        if (preg_match('/[=,; \t\r\n\013\014]/', $name)) {
            return false;
        }
        if (!$encode && preg_match('/[,; \t\r\n\013\014]/', $value)) {
            return false;
        }
        $parts = [$name . '=' . ($encode ? urlencode($value) : $value)];
        if ($expires > 0) {
            $parts[] = 'Expires=' . gmdate('D, d-M-Y H:i:s T', $expires);
            $parts[] = 'Max-Age=' . max(0, $expires - time());
        }
        if ($path !== '') {
            $parts[] = 'Path=' . $path;
        }
        if ($domain !== '') {
            $parts[] = 'Domain=' . $domain;
        }
        if ($secure) {
            $parts[] = 'Secure';
        }
        if ($httponly) {
            $parts[] = 'HttpOnly';
        }
        if ($samesite !== '') {
            $parts[] = 'SameSite=' . $samesite;
        }
        if ($priority !== '') {
            $parts[] = 'Priority=' . $priority;
        }
        if ($partitioned) {
            $parts[] = 'Partitioned';
        }
        return implode('; ', $parts);
    }

    public function status(int $http_code, string $reason = ''): bool
    {
        if (!$this->isWritable()) {
            return false;
        }
        if (!headers_sent()) {
            if ($reason === '' && ($http_code < 100 || $http_code > 599)) {
                $http_code = 200;
            }
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header("$protocol $http_code $reason");
        }
        return true;
    }

    public function setStatusCode(int $http_code, string $reason = ''): bool
    {
        return $this->status($http_code, $reason);
    }

    public function header(string $key, array|string $value, bool $format = true): bool
    {
        if (!$this->isWritable() || headers_sent()) {
            return false;
        }
        $key = $format ? $this->formatHeaderKey($key) : $key;
        if ($key === '' || preg_match('/[:\r\n]/', $key)) {
            return false;
        }
        $values = is_array($value) ? $value : [$value];
        header_remove($key);
        $sent = false;
        foreach ($values as $item) {
            if ($item === null) {
                continue;
            }
            header($key . ': ' . $this->formatHeaderValue($item), !$sent);
            $sent = true;
        }
        $this->headers[$key] = is_array($value) ? array_values($values) : $value;
        return true;
    }

    public function setHeader(string $key, array|string $value, bool $format = true): bool
    {
        return $this->header($key, $value, $format);
    }

    public function trailer(string $key, string $value): bool
    {
        return false;
    }

    protected bool $isWrite = false;

    public function write(string $content): bool
    {
        if (!$this->isWritable()) {
            return false;
        }
        $this->isWrite = true;
        ini_set('output_buffering', 'off');
        header_remove('Content-Length');
        header('Transfer-Encoding: chunked');
        echo $content;
        flush();
        if (ob_get_level() > 0) {
            ob_flush();
        }
        return true;
    }

    public function end(?string $content = null): bool
    {
        if (!$this->isWritable()) {
            return false;
        }
        if(!headers_sent()){
           header_remove('Connection');
           header('Connection: close');
       }
        $this->end = true;
        if ($this->isWrite) {
            echo "";
            if (function_exists('ob_end_flush')) {
                ob_end_flush();
            }
        } else {
            if ($content !== null) {
                echo $content;
            }
        }
        exit();
    }

    public function sendfile(string $filename, int $offset = 0, int $length = 0): bool
    {
        if ($this->isWrite) {
            return false;
        }
        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }
        $fileSize = filesize($filename);
        if ($fileSize === false) {
            return false;
        }
        $offset = max(0, $offset);
        if ($offset >= $fileSize) {
            return false;
        }
        $length = $length <= 0 ? $fileSize - $offset : min($length, $fileSize - $offset);
        if (!$this->isWritable()) {
            return false;
        }
        if (!headers_sent()) {
            header_remove('Content-Length');
            header('Content-Length: ' . $length);
        }
        try {
            if ($offset > 0 || $length < $fileSize) {
                $fp = fopen($filename, 'rb');
                if ($fp === false) {
                    return false;
                }
                fseek($fp, $offset);
                $remaining = $length;
                while ($remaining > 0 && !feof($fp)) {
                    $chunkSize = min(8192, $remaining);
                    echo fread($fp, $chunkSize);
                    $remaining -= $chunkSize;
                    flush();
                }
                fclose($fp);
            } else {
                readfile($filename);
            }
        } catch (Throwable $e) {
            return false;
        }
        $this->end = true;
        return true;
    }

    public function redirect(string $location, int $http_code = 302): bool
    {
        if (!$this->isWritable() || headers_sent()) {
            return false;
        }
        header("Location: $location", true, $http_code);
        return $this->end();
    }

    public function detach(): bool
    {
        $this->detach = true;
        return false;
    }

    public function create(object|array|int $server = -1, int $fd = -1): self|false
    {
        return false;
    }

    protected function formatHeaderKey(string $key): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
    }

    protected function formatHeaderValue(mixed $value): string
    {
        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }
        return trim(str_replace(["\r", "\n"], '', (string)$value));
    }

}
