# Installation

Install the package in a PMS project:

```bash
composer require superpms/interpreter-http
```

The package requires:

- `php >= 8.1`
- `superpms/basic`
- `ext-fileinfo`

It declares `superpms/interpreter-terminal` as a development dependency because the package can register `dev-http-server` when terminal command support exists.

## Composer Autoload

The package exposes:

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

`autoload.files` is important because interpreter registration is side-effect based. If `bin/autoload.php` is not loaded, `InterpreterHook::mount('http', ...)` will not run and PMS Boot will not know the `http` interpreter.

## Development Command Availability

`bin/autorun.php` checks whether `pms\hook\TerminalCommandHook` exists before mounting `DevHttpServerCommand`. Projects without the terminal interpreter can still use the HTTP interpreter, but will not receive the `php pms dev-http-server` command from this package.
