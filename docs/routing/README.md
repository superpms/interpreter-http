# Routing

Routing is split between path parsing in `HttpRoute` and explicit mappings in `pms\program\httpRouter\Driver`.

## Read Order

1. [Route Resolution](route-resolution.md)
2. [HttpRouter](http-router.md)
3. [Forwarding And Coroutine](forwarding-and-coroutine.md)

## Routing Boundary

This package decides how a request path becomes an HTTP class. It does not define business endpoint semantics, permissions, or validation.
