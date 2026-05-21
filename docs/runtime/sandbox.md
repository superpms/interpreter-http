# Sandbox

`pms\interpreter\http\Sandbox` executes a target HTTP class inside a PMS container.

## Run Phases

`Sandbox::run(?string $forward = null)` performs these phases:

1. mark forward mode when `$forward` is supplied
2. apply CORS headers for non-forward requests
3. install `HttpCustomErrorHandler` for non-forward requests
4. create `HttpRoute`
5. handle preflight, lifecycle, static files, app availability, and terminal availability for non-forward requests
6. initialize request data and store it in `Ctx`
7. activate the route with request and response
8. put request, response, and route inject instances into the container
9. execute the target class
10. route exceptions to an exception handler when the response is still writable

## Config Loading

Before app execution, Sandbox calls `initInterpreterConfig()` and loads config in this order:

1. interpreter-level config path: `config/interpreter/{package}`
2. interpreter app config path: `config/interpreter/{package}/app/{app}`
3. app config path:
   - single-terminal app: `app/{app}/{config}`
   - terminal app: `app/{app}/{terminal}/{config}`

The package and config segment names are read from:

- `http.app.structure.package`, default `http`
- `http.app.structure.config`, default `config`

## Static Files

If route `terminal` is in `http.static`, Sandbox serves files from `Path::getWebRoot($pathinfo)`.

`sendFile()` sets:

- `Content-Type` from `mime_content_type()`
- `Content-Length` from `filesize()`

If the file is missing, unreadable, or cannot be read, the response status becomes 404 and the response ends.

## Target Class Checks

`execute()` requires:

- the resolved interface class exists
- the class is a subclass of `pms\app\HttpApp`

If either check fails, Sandbox throws `ClassNotFoundException`.

## Lifecycle Events

For non-forward requests, Sandbox runs:

- `LIFECYCLE_SANDBOX_CREATED`
- `LIFECYCLE_SANDBOX_BOOT`
- `LIFECYCLE_SANDBOX_BOOTED`
- `LIFECYCLE_SANDBOX_RAN`

On destruction, Sandbox runs:

- `LIFECYCLE_SANDBOX_DESTRUCT`

The destructor event is not guarded by forward mode; any Sandbox instance can trigger it when destroyed.
