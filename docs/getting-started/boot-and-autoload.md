# Boot And Autoload

## Autoload Entry

`bin/autoload.php` loads two files:

```php
require_once __DIR__ . '/autorun.php';
require_once __DIR__ . '/const.php';
```

`bin/autorun.php` mounts the interpreter:

```php
InterpreterHook::mount(
    'http',
    Interpreter::class
);
```

The mount name is the Boot property name. Accessing `$boot->http` causes PMS Boot to call `InterpreterHook::run('http')`.

## Constants

`bin/const.php` defines package-level constants:

- `HTTP_APP_MODE_MULTIPLE`
- `HTTP_APP_MODE_SINGLE`
- `HTTP_ROUTE_MODE_APP`
- `HTTP_ROUTE_MODE_TERMINAL`

It also defines `HttpCustomErrorHandler()`, which converts PHP errors into `pms\exception\ErrorException`. The HTTP sandbox installs this handler for non-forward requests before executing the interface class.

## Minimal Boot Entry

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$boot = new \pms\Boot(dirname(__DIR__));
$boot->http;
```

`Boot` itself belongs to `superpms/basic`. This package only supplies the `http` interpreter mounted into Boot.

## Expected Root Inputs

At runtime, the PMS project must have the normal PMS Boot inputs, including `boot.json` and config loading from `superpms/basic`. This package then reads the `http` config namespace during route and sandbox execution.
