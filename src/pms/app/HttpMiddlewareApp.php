<?php

namespace pms\app;

use pms\Container;
use pms\contract\AppInterface;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;
use pms\interpreter\http\sandbox\HttpRoute;
use ReflectionClass;

abstract class HttpMiddlewareApp implements AppInterface
{

    protected string $app;
    protected string $terminal;
    protected string $interface;


    final public function __construct(
        protected HttpRequestInject $request,
        protected HttpResponseInject $response,
        protected HttpRoute $route,
        protected ReflectionClass   $class
    )
    {
        $this->app = $this->route->app;
        $this->terminal = $this->route->terminal;
        $this->interface = $this->route->interface;
    }

    public function callback(Container &$server): void
    {

    }

}