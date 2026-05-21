# Exception Handling

## Sandbox Exception Path

`Sandbox::run()` catches `Throwable`. If the response is still writable, it sets the response content type and calls `exceptionHandle($e)`.

The handler selection is:

1. app or terminal custom handler from `$this->route->exceptionClass()`
2. fallback to `pms\HttpExceptionHandle` if custom handling fails

`CliModeForcedInterruptException` is handled specially: Sandbox sets status code 500 and ends with an empty string.

## Route Exception Handler Resolution

`HttpRoute::exceptionClass()` reads:

- `http.exception.app`, default `HttpExceptionHandle`
- `http.exception.default`, default empty string

If the route is single-terminal app mode, it looks for:

```text
\{appRoot}\{app}\{handlerName}
```

Otherwise it looks for:

```text
\{appRoot}\{app}\{terminal}\{handlerName}
```

If that class does not exist, it tries `http.exception.default`. If that also does not exist, it falls back to `pms\HttpExceptionHandle`.

## HttpExceptionHandle

Constructor:

```php
public function __construct(bool $debug, Throwable $exception, Closure $statusCode)
```

The status closure lets the handler update the HTTP status through the response object.

Default exception mappings include:

- `SystemException`
- `ErrorException`
- `ClassNotFoundException`
- `FuncNotFoundException`

The handler produces an array with at least:

- `message`
- `code`

When debug is enabled, it also includes file, line, trace, and trace string when available.

## Extension Point

Custom exception handlers can extend `pms\HttpExceptionHandle` and override:

```php
protected function autoHandle(Throwable $exception, Closure $statusCode) {}
```

The base class then continues through `process()`. Custom handlers can also call the final protected `setHandleCode()` from inside class methods to register exception mappings and closures.
