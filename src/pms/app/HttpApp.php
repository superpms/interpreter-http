<?php
declare(strict_types=1);

namespace pms\app;

use pms\contract\AppInterface;

abstract class HttpApp implements AppInterface
{

    /**
     * @var string 响应数据类型
     */
    protected string $contentType = JSON_CONTENT_TYPE;

    /**
     * @var array|string 接口中间件
     */
    protected array|string $middleware = [];

    /**
     * 响应数据载体
     * @var mixed|null
     */
    protected mixed $resRaw = null;

    /**
     * @var string 当前应用名称
     */
    public string $app;

    /**
     * @var bool 当前是否处于转发子请求内
     */
    public bool $inForward = false;


    /**
     * @var string 当前终端名称
     */
    public string $terminal;

    /**
     * entry的前置执行方法，且在当前接口实例化完成之后
     * @return void
     */
    public function __prepare(): void{}


    /**
     * entry的后置执行方法
     * @return void
     */
    public function __teardown(): void{}

}