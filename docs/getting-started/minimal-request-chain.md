# Minimal Request Chain

This is the smallest useful chain from Boot to an HTTP class.

## 1. Boot Dispatch

```php
$boot = new \pms\Boot($projectRoot);
$boot->http;
```

Boot dispatches the `http` interpreter that this package mounted during Composer autoload.

## 2. Interpreter Creates Sandbox Objects

`pms\interpreter\http\Interpreter::entry()` creates:

- `pms\interpreter\http\sandbox\HttpRequest`
- `pms\interpreter\http\sandbox\HttpResponse`

It registers HTTP error handling, runs early HTTP lifecycle events, mounts `WebRoot`, and starts `new Sandbox($request, $response)->run()`.

## 3. Sandbox Resolves The Route

`Sandbox::run()` creates `HttpRoute` from `$request->pathinfo()`. The route object:

- derives `app`, `terminal`, and `interface` from the path
- checks whether the request is for a static terminal
- checks whether the app is provided
- checks whether the app-terminal pair is excluded
- loads app route mappings through `HttpRouter`
- calculates the target interface class

## 4. Sandbox Injects Runtime Objects

Before the target class is created, Sandbox puts these instances into its container:

- `HttpRequestInject::class => HttpRequest`
- `HttpResponseInject::class => HttpResponse`
- `HttpRouteInject::class => HttpRoute`

Properties annotated with `#[Inject(...)]` can receive these objects when the target class is instantiated through the PMS container.

## 5. Sandbox Runs The Target

The target class must extend `pms\app\HttpApp`. Sandbox then:

1. initializes interpreter and app config
2. runs configured middleware
3. creates the HTTP app instance
4. sets `$app`, `$terminal`, and `$inForward`
5. calls `__prepare()`
6. calls `entry()`
7. calls `__teardown()`
8. uses `$resRaw` if `entry()` returned `null`
9. serializes the result according to `$contentType`
10. writes and ends the response

## Minimal Interface Example

```php
<?php

namespace app\demo\http;

use pms\annotate\Inject;
use pms\app\HttpApp;
use pms\inject\HttpRequestInject;

class Index extends HttpApp
{
    #[Inject(HttpRequestInject::class)]
    protected HttpRequestInject $request;

    public function entry(): array
    {
        return [
            'code' => 200,
            'path' => $this->request->pathinfo(),
        ];
    }
}
```
