# Public Classes

## Startup

| Class/File | Role |
| --- | --- |
| `bin/autoload.php` | Composer file autoload entry. |
| `bin/autorun.php` | Mounts the `http` interpreter and optional development command. |
| `bin/const.php` | Defines HTTP mode constants and `HttpCustomErrorHandler()`. |

## App Base Classes

| Class | Role |
| --- | --- |
| `pms\app\HttpApp` | Base class for HTTP interface classes. |
| `pms\app\HttpMiddlewareApp` | Base class for HTTP middleware classes. |

## Interpreter Runtime

| Class | Role |
| --- | --- |
| `pms\interpreter\http\Interpreter` | Boot-mounted interpreter entry for normal HTTP runtime. |
| `pms\interpreter\http\Sandbox` | Request execution container and response writer. |
| `pms\interpreter\http\sandbox\HttpRequest` | PHP-global-backed request implementation. |
| `pms\interpreter\http\sandbox\HttpResponse` | PHP-header-backed response implementation. |
| `pms\interpreter\http\sandbox\HttpRoute` | Route parser, resolver, exception handler resolver, and forwarder. |

## Routing

| Class | Role |
| --- | --- |
| `pms\facade\HttpRouter` | Facade for route driver access. |
| `pms\program\httpRouter\Driver` | Stores route mappings and terminal aliases. |
| `pms\program\httpRoute\HttpRouteCoroutine` | Stores forward results and request-context helpers. |

## Injection Contracts

| Interface | Role |
| --- | --- |
| `pms\inject\HttpRequestInject` | Request methods available to apps and middleware. |
| `pms\inject\HttpResponseInject` | Response methods available to apps and middleware. |
| `pms\inject\HttpRouteInject` | Route and forwarding methods available to apps. |

## Hooks And Errors

| Class | Role |
| --- | --- |
| `pms\hook\HttpLifecycleHook` | HTTP lifecycle event container. |
| `pms\HttpExceptionHandle` | Default request exception formatter. |

## Commands

| Class | Role |
| --- | --- |
| `pms\program\http\DevHttpServerCommand` | Registers `dev-http-server` when terminal commands are available. |
