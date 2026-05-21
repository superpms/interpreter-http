# Forwarding And Coroutine

HTTP route forwarding lets one HTTP app execute another HTTP class inside the same request and response context.

## Forward

```php
$coroutine = $this->route->forward(\app\demo\http\Other::class);
```

`HttpRoute::forward($forwardClass)` requires the route to be activated. If the route has no request or response object, it throws `SystemException`.

Forwarding creates:

```php
new Sandbox($this->request, $this->response)
```

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

The method signature accepts an optional class name, but the current implementation returns the full result container. Treat class-specific lookup as a reserved intent unless the implementation is corrected.

## Event And Context Helpers

`HttpRouteCoroutine` also provides:

- `listener($event, callable $listener)`
- `trigger($event, array $data = [])`
- `set($key, $value)`
- `get($key, $default = null)`

These helpers use `Ctx` and are request-context utilities, not a separate asynchronous runtime.

## Response Caution

Forwarding reuses the same response object. If the forwarded target ends, redirects, detaches, or writes to the response, the original caller must account for `HttpResponse::isWritable()` becoming false.
