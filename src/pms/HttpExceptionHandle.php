<?php

namespace pms;

use pms\exception\ClassNotFoundException;
use pms\exception\FuncNotFoundException;
use pms\exception\SystemException;
use pms\exception\WarningException;
use pms\inject\HttpResponseInject;

class HttpExceptionHandle{

    /**
     * 状态码
     * @var array|int[]
     */
    protected array $handleCodeMap = [
        SystemException::class=>501,
        WarningException::class=>502,
        ClassNotFoundException::class=>504,
        FuncNotFoundException::class=>504,
    ];

    protected bool $debug;

    protected mixed $content;

    protected HttpResponseInject $response;

    final protected function setHandleCode($handle,int $code): void
    {
        $this->handleCodeMap[$handle] = $code;
    }

    final public function getContent(): mixed{
        return $this->content;
    }

    final public function __construct(bool $debug,\Throwable $exception,\Closure $statusCode){
        $this->debug = $debug;
        $this->content = $this->handle($exception,$statusCode);
    }

    public function handle(\Throwable $exception, \Closure $statusCode): array{
        $result = [];
        if(isset($this->handleCodeMap[$exception::class])) {
            $result['code'] = $this->handleCodeMap[$exception::class];
        }else if(method_exists($exception,"getCode")) {
            $result['code'] = $exception->getCode();
        }else{
            $result['code'] = 500;
        }
        if(method_exists($exception,"getMessage")) {
            $result['message'] = $exception->getMessage();
        }
        if($this->debug){
            if(method_exists($exception,"getFile")) {
                $result['file'] = $exception->getFile();
            }
            if(method_exists($exception,"getLine")) {
                $result['line'] = $exception->getLine();
            }
            if(method_exists($exception,"getTrace")) {
                $result['trace'] = $exception->getTrace();
            }
        }
        if($exception instanceof SystemException
            || $exception instanceof WarningException){
            $statusCode(500);
            if(!$this->debug){
                $result['message'] = '系统内部错误';
            }
        }else if(
            $exception instanceof ClassNotFoundException
            || $exception instanceof FuncNotFoundException
        ){
            $statusCode(500);
            if(!$this->debug){
                $result['message'] = '类或方法不存在';
            }
        }
        return $result;
    }
}