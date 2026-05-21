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

`constructInterface()` runs only for non-forward routes.

Resolution order:

1. `HttpRouter::load($app)` loads the app route file.
2. `HttpRouter::findClass($pathinfo, ['app' => $app, 'terminal' => $terminal])` checks explicit mappings.
3. If no class is found, terminal aliases are checked through `HttpRouter::findTerminalAlias(...)`.
4. If an alias target class exists, it is used.
5. Otherwise, a namespace is generated from app, terminal, configured package name, and interface.

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
