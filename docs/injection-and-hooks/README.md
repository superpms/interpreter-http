# Injection And Hooks

This section covers how HTTP runtime objects enter app classes and where lifecycle integrations mount.

## Read Order

1. [Inject Attributes](inject-attributes.md)
2. [HttpLifecycleHook](http-lifecycle-hook.md)

## Boundary

The Inject attribute and lifecycle hook mechanism are provided by `superpms/basic`. This package contributes HTTP-specific runtime instances and lifecycle event names.
