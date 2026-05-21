# HttpApp And HttpMiddlewareApp

## HttpApp

`pms\app\HttpApp` is the base class for HTTP interface classes.

Important properties:

- `protected string $contentType = JSON_CONTENT_TYPE`
- `protected array|string $middleware = []`
- `protected mixed $resRaw = null`
- `public string $app`
- `public string $terminal`
- `public bool $inForward = false`

Optional hooks:

- `__prepare()`: called after the app instance is created and before `entry()`
- `__teardown()`: called after `entry()`

`entry()` is inherited from `AppInterface` and must be implemented by the concrete HTTP class.

## Return Values

Sandbox uses the result returned by `entry()`. If `entry()` returns `null` and the class has `resRaw`, Sandbox reads `$resRaw` from the object.

For non-forward requests, Sandbox serializes the result according to the final `$contentType`:

- `JSON_CONTENT_TYPE`: `json_encode($result, 320)`
- `JSONP_CONTENT_TYPE`: `callbackName(json_encode($result))`
- `XML_CONTENT_TYPE`: `array_to_xml($result)`
- other content types: string values are returned as-is; arrays and objects are JSON encoded

## Middleware Property

`$middleware` on an HTTP app can be a class name or an array of class names. Sandbox merges:

1. global `middleware` config
2. the target class default `$middleware` property

The merged list is made unique and executed in order.

## HttpMiddlewareApp

`pms\app\HttpMiddlewareApp` is the base class for HTTP middleware.

Its final constructor receives:

- `HttpRequestInject $request`
- `HttpResponseInject $response`
- `HttpRoute $route`
- `ReflectionClass $class`

It derives:

- `$app` from route
- `$terminal` from route
- `$interface` from route

Middleware classes implement `entry()` through `AppInterface`. After `entry()` runs, Sandbox calls `callback(Container &$server)` when that method exists. The base class defines an empty `callback()`.

## Middleware Failure

If a configured middleware class does not exist, Sandbox throws `SystemException("middleware is not found:...")`. The request then flows into the exception handling path.
