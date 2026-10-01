# Request, Response, Route Objects

This package provides concrete sandbox objects and exposes them through inject interfaces.

## HttpRequest

Concrete class: `pms\interpreter\http\sandbox\HttpRequest`

Interface: `pms\inject\HttpRequestInject`

It reads request data from PHP globals:

- `$_SERVER`
- `$_COOKIE`
- `$_GET`
- `$_POST`
- `$_FILES`
- `php://input`

Important methods:

- `server()`, `header()`, `cookie()`, `get()`, `post()`, `files()`, `params()`
- `input()`, `getContent()`, `rawContent()`
- `contentType()`, `method()`, `pathinfo()`
- `ip()`, `scheme()`, `host()`, `domain()`, `builder()`
- `isHttps()`, `isAjax()`, `isPjax()`, `isPost()`, `isGet()`, `isPut()`, `isDelete()`, `isHead()`, `isOptions()`
- `setAttach()` and `getAttach()` for request-local attached data

`init()` resolves HTTPS and IP from configured trusted headers, derives host and scheme, parses JSON body into `post` when the content type is `application/json`, and builds `params` from GET, POST, and files.

`builder(string|array $path = "", bool $withPrefix = true): string` 使用当前请求的域名生成完整地址。字符串路径和数组路径段沿用现有拼接规则；默认调用 `HttpRoute::withPrefix()` 添加 `http.app.route.prefix`，已有前缀保持一份，空配置保持原业务路径。静态资源地址使用 `builder('/default.jpg', false)`，直接拼接域名和静态路径。套件 GetAssets 通过 HTTP 接口提供资源，使用默认业务前缀，并保留文件路径末尾的 `/`。

## HttpResponse

Concrete class: `pms\interpreter\http\sandbox\HttpResponse`

Interface: `pms\inject\HttpResponseInject`

Important methods:

- `isWritable()`
- `status()` and `setStatusCode()`
- `header()` and `setHeader()`
- `cookie()`, `setCookie()`, `rawcookie()`, `setRawCookie()`
- `write()`
- `end()`
- `sendfile()`
- `redirect()`
- `detach()`
- `create()`

The normal PHP-FPM-style implementation uses PHP `header()`, `echo`, `flush()`, `readfile()`, and `exit()`. Some methods exist for compatibility with Swoole-like response contracts but return `false` in this implementation, such as `trailer()` and `create()`.

## HttpRoute

Concrete class: `pms\interpreter\http\sandbox\HttpRoute`

Interface: `pms\inject\HttpRouteInject`

Route properties are provided through `OptionsAccess` and documented on the interface:

- `pathinfo`
- `app`
- `terminal`
- `terminalMode`
- `interface`
- `interfaceClass`
- `model`
- `isStm`
- `inStatic`
- `inApp`
- `inTerminal`

Important methods:

- `activate(HttpRequestInject $request, HttpResponseInject $response)`
- `forward(string $forwardClass)`
- `getCoroutine()`

`forward()` runs another Sandbox with the same request and response and stores the result in `HttpRouteCoroutine`.
