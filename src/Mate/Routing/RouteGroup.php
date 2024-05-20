<?php

namespace Mate\Routing;

use BadMethodCallException;
use Closure;
use Mate\Http\HttpMethod;
use Mate\Http\HttpNotFoundException;
use Mate\Http\Request;

/**
 * HTTP route group.
 */
class RouteGroup extends RoutingBase
{
    /**
     * Route group prefix.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Route group routes.
     *
     * @var array<string, Route[]>
     */
    protected array $routes = [];

    /**
     * Create a new route group.
     *
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Resolve the route of the `$request`.
     *
     * @param Request $request
     * @return Route
     * @throws HttpNotFoundException when route is not found
     */
    public function resolveRoute(Request $request): Route
    {
        $uri = $request->uri();
        $prefix = $this->prefix;

        if (strlen($prefix) > 0 && !str_starts_with($uri, $prefix)) {
            throw new HttpNotFoundException();
        }

        $uri = substr($uri, strlen($prefix));

        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($uri)) {
                return $route;
            }
        }

        throw new HttpNotFoundException();
    }

    /**
     * Register a new route with the given `$method` and `$uri`.
     *
     * @param HttpMethod $method
     * @param string $uri
     * @param Closure $action
     * @return Route
     */
    protected function registerRoute(HttpMethod $method, string $uri, Closure|array $action, ?string $model = null): Route
    {
        $route = new Route($uri, $action);
        $this->routes[$method->value][] = $route;

        if ($model) {
            $route->model($model);
        } elseif ($this->isImplicitInjection($uri)) {
            $modelInject = $this->isImplicitInjection($uri);
            if ($modelInject) {
                $route->model($modelInject);
            }
        }

        return $route;
    }

    protected function isImplicitInjection(string $uri): mixed
    {
        preg_match_all('/\{(\w+)\}/', $uri, $matches);
        $paramNames = $matches[1];
        foreach ($paramNames as $paramName) {
            $modelName = ucfirst($paramName);
            $fullyQualifiedModelName = "\\App\\Models\\$modelName";
            if (class_exists($fullyQualifiedModelName)) {
                return $fullyQualifiedModelName;
            }
        }
        return null;
    }

    /**
     * Register a GET route with the given `$uri` and `$action`.
     *
     * @param string $uri
     * @param \Closure $action
     * @return Route
     */
    public function get(string $uri, Closure|array $action, ?string $model = null): Route
    {
        return $this->registerRoute(HttpMethod::GET, $uri, $action, $model);
    }

    /**
     * Register a POST route with the given `$uri` and `$action`.
     *
     * @param string $uri
     * @param Closure $action
     * @return Route
     */
    public function post(string $uri, Closure|array $action, ?string $model = null): Route
    {
        return $this->registerRoute(HttpMethod::POST, $uri, $action, $model);
    }

    /**
     * Register a PUT route with the given `$uri` and `$action`.
     *
     * @param string $uri
     * @param Closure $action
     * @return Route
     */
    public function put(string $uri, Closure|array $action, ?string $model = null): Route
    {
        return $this->registerRoute(HttpMethod::PUT, $uri, $action, $model);
    }

    /**
     * Register a PATCH route with the given `$uri` and `$action`.
     *
     * @param string $uri
     * @param Closure $action
     * @return Route
     */
    public function patch(string $uri, Closure|array $action, ?string $model = null): Route
    {
        return $this->registerRoute(HttpMethod::PATCH, $uri, $action, $model);
    }

    /**
     * Register a DELETE route with the given `$uri` and `$action`.
     *
     * @param string $uri
     * @param Closure $action
     * @return Route
     */
    public function delete(string $uri, Closure|array $action, ?string $model = null): Route
    {
        return $this->registerRoute(HttpMethod::DELETE, $uri, $action, $model);
    }

    /**
     * Check if the route group matches the given `$uri`.
     *
     * @param string $uri
     * @return bool
     */
    public function matches(string $uri): bool
    {
        return str_starts_with($uri, $this->prefix);
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, $this->methodsAllowed)) {
            $route = $this->registerRoute($method, ...$parameters);
            return $this->handleRouteAttributes($method, $route, $parameters);
        }

        if (in_array($method, $this->attributesAllowed)) {
            if ($method === 'middleware') {
                return $this->attribute($method, is_array($parameters[0]) ? $parameters[0] : $parameters);
            }

            return $this->attribute($method, array_key_exists(0, $parameters) ? $parameters[0] : true);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }

    protected function handleRouteAttributes($method, $route, $parameters)
    {
        if ($method === 'name') {
            return Route::name($route, ...$parameters);
        } elseif ($method === 'namespace') {
            return Route::namespace($route, ...$parameters);
        } elseif ($method === 'prefix') {
            return Route::prefix($route, ...$parameters);
        } elseif ($method === 'model') {
            return Route::model($route, ...$parameters);
        } elseif ($method === 'middleware') {
            return Route::middleware($route, ...$parameters);
        }

        return $route;
    }
}
