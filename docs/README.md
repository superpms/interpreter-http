# interpreter-http Documentation

This documentation is for developers who maintain or integrate the `superpms/interpreter-http` composer package. It explains how the package is mounted, how requests move through the HTTP sandbox, how routes are resolved, and which extension points are stable.

It intentionally avoids business API documentation. Business interfaces are examples only when they clarify the package contract.

## First Read

1. [Getting Started](getting-started/README.md) for installation, Boot mounting, minimal config, and a minimal HTTP interface.
2. [Runtime](runtime/README.md) for the request lifecycle, `HttpApp`, `HttpMiddlewareApp`, `Interpreter`, `Sandbox`, and sandbox objects.
3. [Routing](routing/README.md) for route config, `HttpRouter`, terminal aliasing, and route forwarding.

## By Problem

- "How is the package loaded?" Read [getting-started/boot-and-autoload.md](getting-started/boot-and-autoload.md).
- "What happens inside one request?" Read [runtime/request-lifecycle.md](runtime/request-lifecycle.md).
- "Why did a path resolve to the wrong class?" Read [routing/route-resolution.md](routing/route-resolution.md).
- "How do I register explicit routes or aliases?" Read [routing/http-router.md](routing/http-router.md).
- "How do injected request, response, and route objects appear in an app?" Read [injection-and-hooks/inject-attributes.md](injection-and-hooks/inject-attributes.md).
- "Where do lifecycle integrations mount?" Read [injection-and-hooks/http-lifecycle-hook.md](injection-and-hooks/http-lifecycle-hook.md).
- "Why did the response show a generic 500 or a trace?" Read [errors-and-debugging/exception-handling.md](errors-and-debugging/exception-handling.md).
- "How do I run a local PHP built-in server?" Read [commands/dev-http-server.md](commands/dev-http-server.md).
- "Which config keys and public classes are part of this package?" Read [reference/README.md](reference/README.md).

## Sections

- [Getting Started](getting-started/README.md): installation, autoload, Boot entry, minimal project shape.
- [Runtime](runtime/README.md): interpreter and sandbox execution model.
- [Routing](routing/README.md): app and terminal parsing, route driver, facade, route forwarding.
- [Injection And Hooks](injection-and-hooks/README.md): Inject Attribute usage and HTTP lifecycle events.
- [Errors And Debugging](errors-and-debugging/README.md): exception handlers, debug output, shutdown fallback.
- [Commands](commands/README.md): package-provided terminal command.
- [Reference](reference/README.md): configuration, class index, extension points.

## Not Here

- Business endpoint documents.
- Tenant, auth, payment, filesystem, or workflow business contracts.
- Project deployment runbooks.
- Swoole-specific interpreter internals, except when comparing boundaries.
