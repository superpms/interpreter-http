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
| `http.app.provide` | `[]` | `HttpRoute` | App names that may be served. |
| `http.app.special` | `[]` | `HttpRoute` | Apps that invert the current terminal mode. |
| `http.app.exclude` | `[]` | `HttpRoute` | Blocked `app.terminal` pairs. |

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
