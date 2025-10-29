<?php

namespace pms\inject;

/**
 * @property string $app
 * @property string $terminal
 * @property string $interface
 * @property string $interfaceClass
 * @property string $stm
 */
interface HttpRouteInject
{
    public function inStatic(): bool;
    public function inApp(): bool;
    public function inTerminal(): bool;
}