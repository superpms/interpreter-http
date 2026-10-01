# Route Resolution

`HttpRoute` is created from the request path and optional forward target.

## Defaults

Before path parsing, `HttpRoute` initializes:

- `app`: `config('http.app.default.app', 'index')`
- `terminal`: `config('http.app.default.terminal', 'index')`
- `terminalMode`: `config('http.app.mode', HTTP_APP_MODE_SINGLE)`
- `interface`: `config('http.app.default.interface', 'Index')`
- `model`: `config('http.app.route.mode', HTTP_ROUTE_MODE_APP)`

If a forward target is supplied, the route is marked as forward and `interfaceClass` is set directly to that class.

## Path Parsing

普通 HTTP 与 Swoole HTTP 都调用 `pms\interpreter\http\Interpreter::dispatch()`，通过 `HttpEntrypointHook` 分发挂载入口。HTTP 内置处理器按 `http.app.route.prefix` 挂载，MCP 处理器按 `mcp.route.prefix` 挂载；路径由处理器在配置加载后提供，采用最长完整路径段匹配，重复挂载路径报告配置错误。

`http.app.route.prefix` 定义外部业务地址的公共前缀。配置 `api` 后，`/api/user/demo/Index` 的应用内部匹配路径为 `/user/demo/Index`。`HttpRoute` 通过挂载层的 `relativePath()` 共用前缀匹配规则，剥离一次后进行应用路由解析；只读接口元数据解析使用同一规则。根静态资源沿用现有静态处理。原请求对象的路径保持。

路由的 `inPrefix` 表示当前前缀准入结果。缺少配置前缀的外部业务请求由 Sandbox 返回 HTTP 404，接口选择停止。内部指定类的转发保留原执行链；`forward()` 自动处理指定路径的公共前缀，调用方继续传入原业务路径。

`analysisPathInfo()` splits `pathinfo` by `/`, removes empty, `.`, and `..` segments, and then maps segments differently by route mode.

In `HTTP_ROUTE_MODE_TERMINAL`:

```text
/{terminal}/{app}/{interface...}
```

In `HTTP_ROUTE_MODE_APP`:

```text
/{app}/{terminal}/{interface...}
```

Segments after app and terminal are joined with `\` to form the logical interface name.

## Terminal Mode

`useTerminalMode()` reads:

- `http.app.mode`
- `http.app.special`

In multiple-terminal mode, apps listed in `special` are treated as single-terminal apps. In single-terminal mode, apps listed in `special` are treated as terminal apps. The computed boolean is stored as `isStm`.

## Static, App, And Terminal Gates

`calcInStatic()` checks whether the current terminal is in `http.static`.

`calcInApp()` checks whether current app is in `http.app.provide`.

`calcInTerminal()` blocks app-terminal pairs listed in `http.app.exclude`, using the key shape:

```text
{app}.{terminal}
```

Sandbox rejects missing app or terminal gates with `Gateway Not Found`.

## Interface Class Construction

`constructInterface()` runs only for non-forward routes that pass the configured prefix gate.

Resolution order:

1. `HttpRouter::load($app)` loads the app route file.
2. `HttpRouter::findClass($pathinfo, ['app' => $app, 'terminal' => $terminal])` checks exact explicit mappings using the internal path.
3. Terminal alias directories are checked before the original terminal directory; an existing class is selected.
4. When no complete-path interface exists, `HttpRouter::findDynamic()` selects the longest matching dynamic prefix, selects its interface, and returns its trailing path parameters.
5. When no match exists, a candidate namespace is generated and the execution layer reports the missing interface.

`constructInterface()` only selects the interface and returns dynamic parameters. `activate()` merges those parameters into the request. Routing declarations use internal paths and remain independent of the configured external prefix.

Namespace generation reads:

- `http.structure_name.package`, default `http`

For single-terminal app mode:

```text
\app\{app}\{packageName}\{interface}
```

For terminal app mode:

```text
\app\{app}\{terminal}\{packageName}\{interface}
```

Note: config loading in Sandbox uses `http.app.structure.package`, while namespace generation currently reads `http.structure_name.package`. Keep both code paths in mind when changing structure-related config.
