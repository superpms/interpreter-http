# Reference

Reference documents collect stable package contracts and public entry points.

## Files

- [Configuration](configuration.md)
- [Public Classes](public-classes.md)
- [Extension Points](extension-points.md)

## Package Surface Summary

- Interpreter name: `http`
- Base app class: `pms\app\HttpApp`
- Base middleware class: `pms\app\HttpMiddlewareApp`
- Request interface: `pms\inject\HttpRequestInject`
- Response interface: `pms\inject\HttpResponseInject`
- Route interface: `pms\inject\HttpRouteInject`
- Route facade: `pms\facade\HttpRouter`
- Lifecycle hook: `pms\hook\HttpLifecycleHook`
- Default exception handler: `pms\HttpExceptionHandle`
- Development command: `dev-http-server`
