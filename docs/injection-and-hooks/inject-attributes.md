# Inject Attributes

HTTP apps and middleware are created through the PMS container. After instantiation, `AnnotationPropertyHook` inspects PHP attributes and applies registered handlers.

## Inject Contract

`pms\annotate\Inject` is a PHP 8 attribute:

```php
#[Inject(SomeClass::class)]
```

Its constructor shape is:

```php
public function __construct(string $classname, ...$args)
```

The first argument is the injected class name. Remaining arguments are passed as constructor arguments if the container needs to instantiate the dependency.

## HTTP Runtime Injections

Before the target class is invoked, Sandbox puts these objects into its container:

```php
$this->put(HttpRequestInject::class, $this->request);
$this->put(HttpResponseInject::class, $this->response);
$this->put(HttpRouteInject::class, $this->route);
```

An HTTP app can then request them:

```php
use pms\annotate\Inject;
use pms\app\HttpApp;
use pms\inject\HttpRequestInject;
use pms\inject\HttpResponseInject;
use pms\inject\HttpRouteInject;

class Index extends HttpApp
{
    #[Inject(HttpRequestInject::class)]
    protected HttpRequestInject $request;

    #[Inject(HttpResponseInject::class)]
    protected HttpResponseInject $response;

    #[Inject(HttpRouteInject::class)]
    protected HttpRouteInject $route;

    public function entry(): array
    {
        return [
            'code' => 200,
            'path' => $this->request->pathinfo(),
            'app' => $this->route->app,
        ];
    }
}
```

## Container Reuse

The Inject handler first checks whether the container already has the requested class name. If it does, it reuses the existing object. If not, it instantiates the class with reflected constructor arguments and then stores it back into the container.

For request, response, and route, Sandbox has already stored the concrete runtime objects, so the app receives the current request context.

## Constructor Injection In Middleware

`HttpMiddlewareApp` receives request, response, route, and target reflection through its constructor. This is separate from property attribute injection and is wired by `Sandbox::runMiddleware()`.
