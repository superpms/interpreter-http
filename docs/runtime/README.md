# Runtime

The runtime section describes the classes that execute one HTTP request.

## Read Order

1. [Request Lifecycle](request-lifecycle.md)
2. [HttpApp And HttpMiddlewareApp](http-app-and-middleware.md)
3. [Interpreter](interpreter.md)
4. [Sandbox](sandbox.md)
5. [Request, Response, Route Objects](request-response-route.md)

## Runtime Boundary

This package owns the generic HTTP runtime. It does not decide business authentication, validation, access control, or response envelope conventions beyond serialization and default error output.
