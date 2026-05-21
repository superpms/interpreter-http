# Getting Started

Use this section to mount the HTTP interpreter in a PMS project and verify the smallest request chain.

## Read Order

1. [Installation](installation.md)
2. [Boot And Autoload](boot-and-autoload.md)
3. [Minimal Request Chain](minimal-request-chain.md)

## Package Contract

The package contributes three startup assets:

- Composer autoload file: `bin/autoload.php`
- Interpreter registration: `bin/autorun.php`
- HTTP constants and PHP error adapter: `bin/const.php`

After Composer loads the package, PMS Boot can dispatch `$boot->http` and the framework will enter `pms\interpreter\http\Interpreter`.
