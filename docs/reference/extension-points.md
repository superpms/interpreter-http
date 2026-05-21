# Extension Points

## Add An HTTP Interface

Extend `pms\app\HttpApp` and implement `entry()`.

```php
class Index extends \pms\app\HttpApp
{
    public function entry(): array
    {
        return ['code' => 200];
    }
}
```

Use `#[Inject(...)]` for `HttpRequestInject`, `HttpResponseInject`, or `HttpRouteInject` when the class needs runtime context.

## Add Middleware

Extend `pms\app\HttpMiddlewareApp` and implement `entry()`.

Register middleware globally through `middleware` config or per interface through the target class `$middleware` default property.

## Add Explicit Routes

Create or edit the configured app route file, usually:

```text
app/{app}/http_router.php
```

Use:

```php
HttpRouter::pusher($path, $class);
HttpRouter::provider($class, [$pathA, $pathB]);
HttpRouter::terminalAlias($app, $realTerminal, $aliasTerminal);
```

## Customize Exception Output

Create an exception handler class in the app or terminal namespace expected by `HttpRoute::exceptionClass()`, or configure `http.exception.default`.

Prefer extending:

```php
pms\HttpExceptionHandle
```

and override `autoHandle()` for custom mappings.

## Mount Lifecycle Integrations

Use `HttpLifecycleHook::mount($event, $callback)` for package-level integrations that need request lifecycle access.

Common uses:

- initialize resources at `LIFECYCLE_BOOT`
- inspect request, response, and route at `LIFECYCLE_SANDBOX_CREATED`
- release request-scoped resources at `LIFECYCLE_SANDBOX_DESTRUCT`

## Change Routing Semantics

Routing semantics live mainly in:

- `HttpRoute::analysisPathInfo()`
- `HttpRoute::useTerminalMode()`
- `HttpRoute::constructInterface()`
- `Driver::pusher()`
- `Driver::provider()`
- `Driver::terminalAlias()`
- `Driver::_findClass()`
- `Driver::_findTerminalAlias()`

Changes here affect all apps using the interpreter, so keep route mode, template expansion, and namespace generation synchronized with config and docs.

## Add A Development Command

This package currently registers only `DevHttpServerCommand`. Additional package commands should be mounted through terminal command hooks only when terminal support exists, following the same optional-dependency pattern used in `bin/autorun.php`.
