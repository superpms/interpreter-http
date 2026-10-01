# Configuration

This package reads configuration through the global `config()` helper. The main namespace is `http`.

## App Routing

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.app.default.app` | `index` | `HttpRoute` | Default app when the path does not provide one. |
| `http.app.default.terminal` | `index` | `HttpRoute` | Default terminal when the path does not provide one. |
| `http.app.default.interface` | `Index` | `HttpRoute` | Default interface name. |
| `http.app.mode` | `HTTP_APP_MODE_SINGLE` | `HttpRoute` | Single-terminal or multiple-terminal mode. |
| `http.app.route.mode` | `HTTP_ROUTE_MODE_APP` | `HttpRoute` | Whether paths are parsed as app-first or terminal-first. |
| `http.app.route.file` | `http_router.php` | `Driver` | App route file loaded by `HttpRouter::load($app)`. |
| `http.app.route.prefix` | `''` | `Interpreter`, `HttpEntrypointHook`, `HttpRoute` | 内置 HTTP 业务挂载路径，配置 `api` 后外部地址为 `/api/{原路由}`；空值表示直接使用应用路由。 |
| `http.app.provide` | `[]` | `HttpRoute` | App names that may be served. |
| `http.app.special` | `[]` | `HttpRoute` | Apps that invert the current terminal mode. |
| `http.app.exclude` | `[]` | `HttpRoute` | Blocked `app.terminal` pairs. |

业务前缀按完整路径段校验，并在应用路由拆分前剥离一次。显式映射、动态前缀和应用路由文件使用应用内部路径。外部业务请求缺少配置前缀时返回 HTTP 404，OPTIONS 请求同样遵守前缀准入。原路径命中的静态资源保持原地址。

`HttpRoute::withPrefix($path)` 统一生成外部业务路径，已带当前前缀的路径保持原值。`forward()` 自动为显式指定的转发路径补齐前缀；共享请求保持原请求，内部指定类的转发保留原有应用、终端、参数和认证执行链。

## App Structure

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.app.structure.package` | `http` | `Sandbox` | Config package segment for interpreter/app config loading. |
| `http.app.structure.config` | `config` | `Sandbox` | App config segment name. |
| `http.structure_name.package` | `http` | `HttpRoute` | Namespace package segment used when generating interface class names. |

The current code uses different config keys for config-path loading and namespace generation. Keep them aligned in project config when changing app structure.

## Static And Web Root

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.static` | `[]` | `HttpRoute`, `Sandbox` | Terminal names treated as static resource roots. |
| `http.web_root` | `/public` | `Interpreter` | Mounted as `WebRoot`. |
| `http.root` | `public` | `DevHttpServerCommand` | Default root for PHP built-in development server. |

## Request Metadata

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.header.scheme_name` | `x-forwarded-scheme` | `HttpRequest` | Header name or names used to infer HTTPS. |
| `http.header.ip_name` | `x-real-ip` | `HttpRequest` | Header name or names used to infer client IP. |

`HttpRequest` also falls back to `x-forwarded-proto`, `HTTPS`, `SERVER_PORT`, and `REMOTE_ADDR` when deriving scheme and IP.

## CORS

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.cors` | `[]` | `Sandbox` | Response headers applied to non-forward requests before route execution. |

Array values are joined with commas before being written as headers.

## Exceptions

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `http.exception.app` | `HttpExceptionHandle` | `HttpRoute` | App-local exception handler class name. |
| `http.exception.default` | empty string | `HttpRoute` | Global fallback exception handler class. |

If neither class exists, `pms\HttpExceptionHandle` is used.

## Middleware

| Key | Default | Used By | Meaning |
| --- | --- | --- | --- |
| `middleware` | `[]` | `Sandbox` | Global middleware class or classes merged with app class middleware. |

`middleware` is read as a top-level config key, not under `http`.

## 配置模板安装

包通过 extra.pms.config 声明模板，宿主根项目 post-autoload-dump 执行 @php pms vendor:install:hook。安装钩子将缺失的 http.php、interpreter/http/middleware.php 复制到 Path::getConfig()，保留已有文件。

resource/config.php 按当前代码消费键提供 root/web_root、app.structure/structure_name、api 业务前缀、终端优先路由、多终端模式及 index 应用名单。业务名单、异常处理器、中间件由宿主维护。Swoole HTTP 包独立安装 http-swoole.php，启动命令通过 config('http-swoole', []) 读取 host、port、config。

普通 HTTP 与 Swoole HTTP 共用 Interpreter::dispatch()；HttpEntrypointHook::mount() 注册处理器类，处理器实现 HttpEntrypointInterface::prefixes()/handle()。prefixes() 在请求分发时读取已加载的宿主配置，按最长完整路径段选择处理器，重复路径报告配置错误。HTTP 内置挂载点读取 http.app.route.prefix；MCP 挂载点读取 mcp.route.prefix，OAuth 元数据入口同步生成。HTTP 业务 Sandbox 保持业务与静态资源执行职责；内部 forward() 直接进入 Sandbox。mergeRouteParams() 将可信路径参数注入请求集合并保留原始 JSON。
