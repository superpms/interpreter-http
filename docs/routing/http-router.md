# HttpRouter

Facade: `pms\facade\HttpRouter`

Driver: `pms\program\httpRouter\Driver`

The facade resolves to one `Driver` instance through the PMS facade system.

## Route File Loading

`Driver::_load($app)` reads the route file name from:

```php
config('http.app.route.file', 'http_router.php')
```

It then loads:

```text
app/{app}/{routeFile}
```

Each app route file is loaded lazily and only once per Driver instance.

## Register A Route

```php
use pms\facade\HttpRouter;

HttpRouter::pusher('/{$terminal}/demo/Index', \app\demo\tenant\http\Index::class);
```

`pusher($path, $class)` writes one path-to-class mapping. Registering the same path again overwrites the previous class in the container.

## Register Multiple Paths

```php
HttpRouter::provider(\app\demo\tenant\http\Index::class, [
    '/tenant/demo/Index',
    '/admin/demo/Index',
]);
```

`provider($class, $paths)` is a convenience wrapper around `pusher()`.

## Register Dynamic Route

```php
HttpRouter::dynamic('/platform/system/kits/Router', 'kitName', 'path');
```

`dynamic($path, ...$args)` maps a path prefix to one fixed request path and converts the remaining path into GET parameters.

For `/platform/system/kits/Router/payment-wechat/Get/GetUserInfo/Item`, the fixed request path is `/platform/system/kits/Router`, and GET parameters become:

```text
kitName=payment-wechat
path=Get/GetUserInfo/Item
```

Parameter splitting follows the parameter count from left to right. When there is one parameter name, the whole remaining path is assigned to it. When there are multiple parameter names, earlier names receive one path segment each, and the last name receives the remaining path.

Dynamic GET parameters have higher priority than query string parameters with the same name.

## Templates

The driver has two built-in templates:

- `{$terminal}` from `useTerminalTemplate()`
- `{$app}` from `useAppTemplate()`

Templates are not expanded when the route file registers them. They are expanded during `findClass()` or `findTerminalAlias()` when an option array is passed.

Example:

```php
$terminal = HttpRouter::useTerminalTemplate();
HttpRouter::pusher("/{$terminal}/demo/Index", \app\demo\tenant\http\Index::class);
```

When `findClass()` receives `['terminal' => 'tenant']`, the route key can become `/tenant/demo/Index`.

## Terminal Alias

```php
HttpRouter::terminalAlias('demo', 'tenant', '{$terminal}');
```

`terminalAlias($app, $terminalName, $aliasTerminalName)` maps an incoming alias terminal to one or more real terminal names.

The internal key shape is:

```text
{app}/{aliasTerminalName}
```

The value is a list of real terminal names. `findTerminalAlias()` returns the list in reverse registration order, so later registrations have higher lookup priority.

Use terminal aliases to reuse a terminal implementation without duplicating HTTP classes.

## Hidden Driver Methods

`load`, `findClass`, `findDynamic`, and `findTerminalAlias` are routed through `Driver::__call()`. They are intentionally not public concrete methods on the class, but they are available through the facade and internal route resolver.
