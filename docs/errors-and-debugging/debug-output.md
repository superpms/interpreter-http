# Debug Output

Debug output is controlled by `BootOptions::get_error_debug()`, which comes from PMS basic boot options.

## Interpreter Basic Error Output

`Interpreter::handleBasicError()` handles errors already stored in `pms_error()`.

When debug is true, the JSON payload includes:

- `message`
- `code`
- `error`
- `file`
- `line`
- `trace`

When debug is false, it returns:

```json
{
  "message": "系统内部错误",
  "code": 500
}
```

This path does not use `HttpExceptionHandle`.

## Request Exception Output

`Sandbox::exceptionHandle()` delegates to `HttpExceptionHandle` or a route-specific handler.

When debug is true, `HttpExceptionHandle::process()` adds detailed exception fields.

When debug is false, mapped internal errors return generic messages such as:

- `系统内部错误`
- `类或方法不存在`

## Serialization After Handling

The exception handler returns content, and Sandbox serializes it using the current response content type. If serialization returns `false`, Sandbox removes `trace` from the content and tries serialization again.

## Practical Rule

Use Boot-level debug config to control whether stack details can leave the server. Use custom exception handlers to change exception mapping or response payload shape.
