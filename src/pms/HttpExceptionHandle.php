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
    protected array $handle = [
        SystemException::class,
        WarningException::class,
        ClassNotFoundException::class,
        FuncNotFoundException::class,
    ];

    protected bool $debug;

    protected mixed $content;

    protected HttpResponseInject $response;

    final public function getContent(): mixed{
        return $this->content;
    }

    final public function __construct(\Throwable $exception,\Closure $statusCode){
        $this->debug = config('app.debug',false);
        $this->content = $this->handle($exception,$statusCode);
    }

    public function handle(\Throwable $exception, \Closure $statusCode): array{
        if($exception instanceof SystemException){
            $statusCode(500);
            $data = [
                'message'=>$exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }else if(
            $exception instanceof ClassNotFoundException
            || $exception instanceof FuncNotFoundException
        ){
            $statusCode(500);
            $data = [
                'message'=>'类或方法不存在',
                'code' => 504,
            ];
            if($this->debug){
                $data['message'] = $exception->getMessage();
                $data['file'] = $exception->getFile();
                $data['line'] = $exception->getLine();
                $data['trace'] = $exception->getTrace();
            }
        }else{
            $data = [
                'message' => $exception->getMessage(),
                'code' => 500,
            ];
            if($this->debug){
                $data['message'] = $exception->getMessage();
                $data['file'] = $exception->getFile();
                $data['line'] = $exception->getLine();
                $data['trace'] = $exception->getTrace();
            }
        }
        return $data;
    }
}