<?php

namespace pms;

use pms\exception\ClassNotFoundException;
use pms\exception\FuncNotFoundException;
use pms\exception\SystemException;
use pms\exception\WarningException;
use pms\inject\HttpResponseInject;
use \Closure;

class HttpExceptionHandle
{

    /**
     * 状态码
     * @var array|int[]
     */
    private array $handleCodeMap = [
        SystemException::class => 501,
        WarningException::class => 502,
        ClassNotFoundException::class => 504,
        FuncNotFoundException::class => 504,
    ];

    /**
     * @var Closure[] 处理函数
     */
    private array $handleClosure = [];

    protected bool $debug;

    private mixed $content;

    protected HttpResponseInject $response;

    final protected function setHandleCode(string|array $handles, int $code, ?Closure $closure = null): void
    {
        if(is_string($handles)){
            $handles = [$handles];
        }
        foreach ($handles as $handle){
            $this->handleCodeMap[$handle] = $code;
            if ($closure !== null) {
                $this->handleClosure[$handle] = $closure;
            }
        }
    }

    final public function getContent(): mixed{
        return $this->content;
    }


    final public function __construct(bool $debug, \Throwable $exception, \Closure $statusCode)
    {
        $this->debug = $debug;
        $this->defaultSet();
        $this->content = $this->handle($exception, $statusCode);
    }

    private function defaultSet(): void{
        $this->setHandleCode([
            SystemException::class,
            WarningException::class
        ], 500,function (\Throwable $exception, \Closure $statusCode){
            $statusCode(500);
            if (!$this->debug) {
                return [
                    'message' => '系统内部错误',
                ];
            }
            return [];
        });


        $this->setHandleCode([
            ClassNotFoundException::class,
            FuncNotFoundException::class
        ], 500,function (\Throwable $exception, \Closure $statusCode){
            $statusCode(500);
            if (!$this->debug) {
                return [
                    'message' => '类或方法不存在',
                ];
            }
            return [];
        });

    }

    final protected function process(\Throwable $exception, \Closure $statusCode)
    {
        $result = [];
        if (isset($this->handleCodeMap[$exception::class])) {
            $result['code'] = $this->handleCodeMap[$exception::class];
        } else if (method_exists($exception, "getCode")) {
            $result['code'] = $exception->getCode();
        } else {
            $result['code'] = 500;
        }
        if (method_exists($exception, "getMessage")) {
            $result['message'] = $exception->getMessage();
        }
        if ($this->debug) {
            if (method_exists($exception, "getFile")) {
                $result['file'] = $exception->getFile();
            }
            if (method_exists($exception, "getLine")) {
                $result['line'] = $exception->getLine();
            }
            if (method_exists($exception, "getTrace")) {
                $result['trace'] = $exception->getTrace();
            }
            if (method_exists($exception, "getTraceAsString")) {
                $result['traceAsString'] = $exception->getTraceAsString();
            }
        }
        if (isset($this->handleClosure[$exception::class])) {
            $res = $this->handleClosure[$exception::class]($exception, $statusCode);
            if(!empty($res) && is_array($res)){
                $result = [
                    ...$result,
                    ...$res,
                ];
            }
        }
        return $result;
    }

    public function handle(\Throwable $exception, \Closure $statusCode): array{
        return $this->process($exception, $statusCode);
    }
}