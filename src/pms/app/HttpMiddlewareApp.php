<?php

namespace pms\app;

use pms\contract\AppInterface;
use pms\inject\HttpRequestInject;
use ReflectionClass;

abstract class HttpMiddlewareApp implements AppInterface {

    protected ReflectionClass $class;
    protected HttpRequestInject $request;
    protected string $app;
    final public function __construct(ReflectionClass $class, HttpRequestInject $request,string $app){
        $this->class = $class;
        $this->request = $request;
        $this->app = $app;
    }



}