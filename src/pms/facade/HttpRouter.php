<?php

namespace pms\facade;

use pms\Facade;
use pms\program\httpRouter\Driver;

/**
 * @see Driver
 * @mixin Driver
 */
class HttpRouter extends Facade
{

    protected static function getFacadeClass(): string
    {
        return Driver::class;
    }
}