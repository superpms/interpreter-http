<?php

namespace pms\app;

use pms\contract\AppInterface;
use pms\inject\HttpRequestInject;
use ReflectionClass;

abstract class HttpMiddlewareApp implements AppInterface
{


    final public function __construct(
        protected ReflectionClass $class,
        protected HttpRequestInject $request,
        protected string $app,
        protected string $terminal,
        protected string $interface
    )
    {
    }


}