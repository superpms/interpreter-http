<?php

namespace pms\interpreter\http\sandbox;


use pms\inject\HttpResponseInject;
use Throwable;

class HttpResponse implements HttpResponseInject
{

    public function __construct(){}

    protected bool $end = false;
    protected bool $detach = false;

    public function isWritable(): bool
    {
        return !$this->end && !$this->detach;
    }

    public function cookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = ''): bool
    {
        return setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
            'priority' => $priority,
        ]);
    }

    public function setCookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = ''): bool
    {
        return $this->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function rawcookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = ''): bool
    {
        $value = rawurlencode($value);
        return setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
            'priority' => $priority,
        ]);
    }

    public function status(int $http_code, string $reason = ''): bool
    {
        if(!headers_sent()){
            header("HTTP/1.1 $http_code $reason");
        }
        return true;
    }

    public function setStatusCode(int $http_code, string $reason = ''): bool
    {
        return $this->status($http_code, $reason);
    }

    public function header(string $key, array|string $value, bool $format = true): bool
    {
        if(!headers_sent()){
            //  $format 是否需要对 Key 进行 HTTP 约定格式化【默认 true 会自动格式化】
            $key = $format ? strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $key)) : $key;
            header_remove($key);
            header("$key: $value");
        }
        return true;
    }

    public function setHeader(string $key, array|string $value, bool $format = true): bool
    {
        return $this->header($key, $value, $format);
    }

    protected array $trailer = [];

    public function trailer(string $key, string $value): bool
    {
        $this->trailer[$key] = $value;
        return true;
    }

    protected bool $isWrite = false;

    public function write(string $content): bool
    {
        $this->isWrite = true;
        ini_set('output_buffering', 'off');
        header('Transfer-Encoding: chunked');
        echo $content;
        flush();
        ob_flush();
        return true;
    }

    public function end(?string $content = null): mixed
    {
       if(!headers_sent()){
           header_remove('Connection');
           header('Connection: close');
       }
        if (!empty($this->trailer)) {
            header_remove('Trailer');
            header('Trailer: ' . implode(', ', array_keys($this->trailer)));
        }
        $this->end = true;
        if ($this->isWrite) {
            echo "";
            if (function_exists('ob_end_flush')) {
                ob_end_flush();
            }
        } else {
            if ($content) {
                echo $content;
            }
        }
        if (!empty($this->trailer)) {
            foreach ($this->trailer as $key => $value) {
                header("$key: $value");
            }
        }
        exit();
    }

    public function sendfile(string $filename, int $offset = 0, int $length = 0): bool
    {
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
        header_remove('Content-Type');
        header('Content-Type: application/octet-stream');
        header_remove('Content-Disposition');
        header('Content-Disposition: attachment; filename="' . addcslashes(basename($filename), '"\\') . '"');
        header_remove('Content-Length');
        header_remove('Connection');
        header('Connection: close');
        header('Content-Length: ' . $length);
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
        header("Location: $location", true, $http_code);
        $this->end();
        return true;
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

}
