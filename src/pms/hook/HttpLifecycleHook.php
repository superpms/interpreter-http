<?php

namespace pms\hook;

use pms\app\LifecycleHookApp;

class HttpLifecycleHook extends LifecycleHookApp
{

    public static array $container = [
        LIFECYCLE_BOOT => [],
        LIFECYCLE_BOOTED => [],
		LIFECYCLE_SERVER_BOOTED => [],
        LIFECYCLE_SANDBOX_CREATED => [],
        LIFECYCLE_SANDBOX_BOOT => [],
        LIFECYCLE_SANDBOX_BOOTED => [],
        LIFECYCLE_SANDBOX_RAN => [],
        LIFECYCLE_SANDBOX_DESTRUCT => [],
    ];

}