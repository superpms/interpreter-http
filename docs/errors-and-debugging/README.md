# Errors And Debugging

This section explains how HTTP errors become responses.

## Read Order

1. [Exception Handling](exception-handling.md)
2. [Debug Output](debug-output.md)
3. [Shutdown And Fatal Fallback](shutdown-and-fatal.md)

## Boundary

There are two main error paths:

- Interpreter-level system error context handled by `Interpreter::handleBasicError()`.
- Request execution exceptions handled by `Sandbox::exceptionHandle()` and `HttpExceptionHandle`.
