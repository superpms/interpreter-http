# superpms/interpreter-http

`superpms/interpreter-http` is the PMS HTTP interpreter package. It mounts the `http` interpreter into PMS Boot, creates the HTTP request and response sandbox, resolves app and terminal routes, injects HTTP runtime objects, runs HTTP middleware and lifecycle hooks, serializes interface return values, and converts errors into HTTP responses.

This repository is package-level documentation for framework and package developers. It describes the interpreter contract and extension points, not business API endpoints.

## Requirements

- PHP `>= 8.1`
- Composer
- `superpms/basic`
- `ext-fileinfo`
- Optional for the development command: `superpms/interpreter-terminal`

## Install

```bash
composer require superpms/interpreter-http
```

The package is loaded through Composer `autoload.files`:

```json
{
  "autoload": {
    "files": [
      "bin/autoload.php"
    ],
    "psr-4": {
      "pms\\": "src/pms/"
    }
  }
}
```

`bin/autoload.php` loads `bin/autorun.php` and `bin/const.php`. The autorun file mounts:

- `InterpreterHook::mount('http', pms\interpreter\http\Interpreter::class)`
- `pms\program\http\DevHttpServerCommand` when the terminal command hook exists

## Boot Mount

In a PMS project, the interpreter is entered by accessing the `http` interpreter on Boot:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$boot = new \pms\Boot(dirname(__DIR__));
$boot->http;
```

At runtime, `Boot::__get('http')` dispatches through `InterpreterHook`, which calls `pms\interpreter\http\Interpreter::entry()`.

## Minimal HTTP Interface

```php
<?php

namespace app\demo\http;

use pms\annotate\Inject;
use pms\app\HttpApp;
use pms\inject\HttpRequestInject;

class Index extends HttpApp
{
    #[Inject(HttpRequestInject::class)]
    protected HttpRequestInject $request;

    public function entry(): array
    {
        return [
            'code' => 200,
            'path' => $this->request->pathinfo(),
        ];
    }
}
```

The default route resolver expects HTTP classes under the configured app and terminal structure. Route mappings and terminal aliases can also be provided through each app's `http_router.php`.

## Runtime Chain

1. Composer loads `bin/autoload.php`.
2. `bin/autorun.php` mounts the `http` interpreter.
3. PMS Boot dispatches `$boot->http`.
4. `Interpreter::entry()` creates `HttpRequest` and `HttpResponse`, mounts `WebRoot`, registers error handling, and starts `Sandbox`.
5. `Sandbox::run()` applies CORS, builds `HttpRoute`, handles static paths and preflight requests, activates route context, injects request/response/route objects, runs middleware, invokes the target `HttpApp`, serializes the result, and ends the response.

## Configuration Shape

This package reads the `http` config namespace. The most important keys are:

- `http.app.mode`: `HTTP_APP_MODE_SINGLE` or `HTTP_APP_MODE_MULTIPLE`
- `http.app.route.mode`: `HTTP_ROUTE_MODE_APP` or `HTTP_ROUTE_MODE_TERMINAL`
- `http.app.route.file`: route file name, default `http_router.php`
- `http.app.provide`: accessible app names
- `http.app.special`: app names that invert the current terminal mode
- `http.app.exclude`: blocked `app.terminal` pairs
- `http.static`: terminal names treated as static-resource roots
- `http.cors`: response headers applied before non-forward requests
- `http.header.scheme_name` and `http.header.ip_name`: trusted request metadata headers
- `http.exception.default` and `http.exception.app`: default and app-local exception handlers

See [docs/reference/configuration.md](docs/reference/configuration.md) for details.

## Development Server

When `superpms/interpreter-terminal` is available, this package registers:

```bash
php pms dev-http-server --host=0.0.0.0 --port=8000 --root=public
```

It runs PHP's built-in server and is intended only for local development or package-level smoke tests.

## Documentation

Start with [docs/README.md](docs/README.md).

- [Getting Started](docs/getting-started/README.md)
- [Runtime](docs/runtime/README.md)
- [Routing](docs/routing/README.md)
- [Injection And Hooks](docs/injection-and-hooks/README.md)
- [Errors And Debugging](docs/errors-and-debugging/README.md)
- [Commands](docs/commands/README.md)
- [Reference](docs/reference/README.md)

## Module Boundary

This package owns the HTTP interpreter runtime and the contracts needed to integrate it with PMS Boot. It does not own business authentication, business validation, domain models, data persistence, tenant policy, or business API documentation. Those belong to project apps or separate composer packages that consume this interpreter.

## License

Apache-2.0. See [LICENSE.txt](LICENSE.txt).
