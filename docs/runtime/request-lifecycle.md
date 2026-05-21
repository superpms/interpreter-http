# Request Lifecycle

## High-Level Flow

```text
Boot::$http
  -> InterpreterHook::run('http')
  -> Interpreter::entry()
  -> new HttpRequest()
  -> new HttpResponse()
  -> new Sandbox($request, $response)->run()
  -> new HttpRoute($request->pathinfo())
  -> route activation
  -> container injection
  -> middleware
  -> HttpApp::__prepare()
  -> HttpApp::entry()
  -> HttpApp::__teardown()
  -> serialize result
  -> HttpResponse::end()
```

## Non-Forward Request Behavior

For a normal request, Sandbox:

- applies CORS headers from `http.cors`
- installs `HttpCustomErrorHandler`
- handles `OPTIONS` by ending the response immediately
- runs `LIFECYCLE_SANDBOX_CREATED`
- serves static files when the route terminal is configured as static
- rejects unavailable app or terminal targets with `Gateway Not Found`
- initializes request data from PHP globals
- stores the request in `Ctx` under `HttpRequestInject::class`

## Forward Request Behavior

`HttpRoute::forward($class)` creates another `Sandbox` with the same request and response, then calls `run($class)`.

Forward execution is not a new HTTP request. It bypasses the route class lookup because the target class is supplied directly. It also skips the non-forward-only parts of Sandbox, including CORS initialization, preflight handling, request initialization, and the outer lifecycle events guarded by `!$this->isForward`.

Forward results are stored through `HttpRouteCoroutine::pushResult($class, $result)`.

## Response End Semantics

`HttpResponse::end()` sets the response as ended, sends `Connection: close` when headers are still writable, writes the optional content, and exits the PHP process. After `end()` or `detach()`, `isWritable()` returns false and later header/body writes fail.
