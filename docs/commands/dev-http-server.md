# Dev HTTP Server

Class: `pms\program\http\DevHttpServerCommand`

Command name:

```text
dev-http-server
```

The command is mounted from `bin/autorun.php` only when `pms\hook\TerminalCommandHook` exists.

## Options

- `--host`: bind address, default `0.0.0.0`
- `--port`: bind port, default `8000`
- `--root`: web root, default `config('http.root', 'public')`

## Run

```bash
php pms dev-http-server --host=0.0.0.0 --port=8000 --root=public
```

The command builds:

```text
{PHP_BINARY} -S {host}:{port} -t {root} {root}/index.php
```

and executes it with `passthru()`.

## Intended Use

Use this command for local development and package smoke tests. Production environments should use the project's chosen HTTP runtime and deployment model.
