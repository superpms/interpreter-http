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

`builder(string|array $path = "", bool|string $prefix = true): string` 使用当前请求域名与 `HttpRoute::builder($path, $prefix)` 生成完整地址。公共 builder 统一处理字符串、数组路径段、反斜线及挂载前缀。prefix=true 使用 http.app.route.prefix，并保留已匹配 HTTP 挂载点的完整路径；false 使用站点根路径；字符串指定当前目标挂载前缀。套件地址传入 config('kits.http.prefix', 'kits')。已有前缀保持一份；资源路径末尾的 `/` 保持。GetAssets 使用默认业务前缀，静态资源使用 builder('/default.jpg', false)。

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
