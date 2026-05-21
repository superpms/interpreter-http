# Shutdown And Fatal Fallback

`Interpreter::customShutDownHandler()` registers a PHP shutdown handler.

## Trigger

At shutdown, the handler checks:

```php
$error = error_get_last();
```

If the result is not empty, it treats the request as failed at shutdown time.

## Response Behavior

The handler:

1. clears the current output buffer with `ob_end_clean()`
2. sets HTTP status 500
3. sets JSON content type
4. ends the response

When debug is enabled, the payload includes:

- `error`
- `type: shutdown`
- `code: 500`
- `message`

When debug is disabled, the payload is the generic internal-error shape.

## Boundary

The shutdown handler is the last fallback for fatal shutdown conditions. Normal exceptions inside request execution should be handled by `Sandbox::exceptionHandle()`, not by relying on shutdown.
