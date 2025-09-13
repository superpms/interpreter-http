<?php

namespace pms\app;

use pms\Container;
use pms\contract\AppInterface;
use pms\inject\HttpRequestInject;
use pms\program\boot\Options;
use ReflectionClass;

abstract class HttpMiddlewareApp implements AppInterface
{


    final public function __construct(
        protected ReflectionClass $class,
        protected HttpRequestInject $request,
        protected string $app,
        protected string $terminal,
        protected string $interface,
        protected Options $bootOptions,
    )
    {
    }

    public function callback(Container &$server): void{}

}