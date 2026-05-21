# HttpLifecycleHook

Class: `pms\hook\HttpLifecycleHook`

`HttpLifecycleHook` extends `LifecycleHookApp` and defines the HTTP lifecycle event container.

## Events

The package initializes containers for:

- `LIFECYCLE_BOOT`
- `LIFECYCLE_BOOTED`
- `LIFECYCLE_SERVER_BOOTED`
- `LIFECYCLE_SANDBOX_CREATED`
- `LIFECYCLE_SANDBOX_BOOT`
- `LIFECYCLE_SANDBOX_BOOTED`
- `LIFECYCLE_SANDBOX_RAN`
- `LIFECYCLE_SANDBOX_DESTRUCT`

## Event Timing And Arguments

`Interpreter::entry()` runs:

- `LIFECYCLE_BOOT`
- `LIFECYCLE_BOOTED`
- `LIFECYCLE_SERVER_BOOTED`

`Sandbox::run()` runs for non-forward requests:

- `LIFECYCLE_SANDBOX_CREATED($request, $response, $route)`
- `LIFECYCLE_SANDBOX_BOOT($request, $response, $route, $class)`
- `LIFECYCLE_SANDBOX_BOOTED($request, $response, $route, $class, $obj)`
- `LIFECYCLE_SANDBOX_RAN($request, $response, $route, $class, $obj, $result)`

`Sandbox::__destruct()` runs:

- `LIFECYCLE_SANDBOX_DESTRUCT`

## Mount Example

```php
use pms\hook\HttpLifecycleHook;

HttpLifecycleHook::mount(LIFECYCLE_SANDBOX_DESTRUCT, function () {
    // release request-scoped resources
});
```

Database or Redis integration packages can use these hooks to configure resources on boot and clean request-scoped resources after sandbox destruction.

## Forwarding Note

Several lifecycle events in Sandbox are guarded by `!$this->isForward`. Forwarded sub-sandbox executions do not run those guarded events. The destructor event still runs when the Sandbox instance is destroyed.
