# Forwarding And Coroutine

HTTP route forwarding lets one HTTP app execute another HTTP class inside the same request and response context.

## Forward

```php
$coroutine = $this->route->forward(\app\demo\http\Other::class);
```

`HttpRoute::forward($forwardClass)` requires the route to be activated. If the route has no request or response object, it throws `SystemException`.

`forward($class, $params, $method, $pathinfo)` supports independent parameters and an explicit request method and path. An explicit path passes through `HttpRoute::withPrefix()` once, so callers can continue passing internal paths. Already-prefixed paths retain one prefix. The target route removes the prefix before app and terminal parsing; the copied request retains the prefixed path for middleware and interface access.

Calling with only a class shares the existing request. Supplying parameters, method, or path clones the native request through `withParams()`, preserving its authenticated attach context and leaving the outer request unchanged. The new Sandbox reuses the response.

and calls:

```php
run($forwardClass)
```

The target class is supplied directly, so normal path-based class construction is skipped.

## Result Storage

`HttpRouteCoroutine::pushResult($class, $result)` stores forward results in `Ctx` under an internal key derived from the coroutine class name.

Read results with:

```php
$all = $coroutine->getResult();
```

`getResult($class)` returns the result for that class. Calling `getResult()` without a class returns the full result container. Forwarded exceptions propagate to the caller.

## Event And Context Helpers

`HttpRouteCoroutine` also provides:

- `listener($event, callable $listener)`
- `trigger($event, array $data = [])`
- `set($key, $value)`
- `get($key, $default = null)`

These helpers use `Ctx` and are request-context utilities, not a separate asynchronous runtime.

## Response Caution

Forwarding reuses the same response object. If the forwarded target ends, redirects, detaches, or writes to the response, the original caller must account for `HttpResponse::isWritable()` becoming false.
