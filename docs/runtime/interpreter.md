# Interpreter

`pms\interpreter\http\Interpreter` is mounted under the name `http`.

## Entry Responsibilities

`Interpreter::entry()`:

1. clears `Ctx` for `HttpRequestInject::class`
2. creates `HttpRequest`
3. creates `HttpResponse`
4. registers a `SystemErrorBroadcast` listener that delegates to `handleBasicError()`
5. runs `LIFECYCLE_BOOT`
6. registers `customShutDownHandler($response)`
7. mounts `WebRoot` from `config('http.web_root', '/public')`
8. runs `LIFECYCLE_BOOTED`
9. runs `LIFECYCLE_SERVER_BOOTED`
10. runs `Sandbox`

It returns `true` after Sandbox completes, though in normal response-ending flows `HttpResponse::end()` exits first.

## Basic Error Handling

`handleBasicError(HttpResponse $response)` consumes errors from the shared `pms_error()` slot:

- if no error exists, it returns
- if an error exists, it clears the slot with `pms_error_clear()`
- it writes JSON with status-like `code => 500`
- when `BootOptions::get_error_debug()` is true, it includes message, error code, file, line, and trace
- when debug is false, it returns a generic internal-error payload

This path is for system-level errors already captured by PMS basic error context. Request execution exceptions are handled later by Sandbox and `HttpExceptionHandle`.

## Shutdown Fallback

`customShutDownHandler(HttpResponse $response)` registers a PHP shutdown function. If `error_get_last()` returns an error at shutdown, it clears the output buffer, sets status 500, sets JSON content type, and ends the response with either debug details or a generic payload.

The shutdown handler is the fatal fallback. It does not replace normal exception handling inside `Sandbox::run()`.
