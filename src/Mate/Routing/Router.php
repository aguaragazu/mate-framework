<?php

namespace Mate\Routing;

use BadMethodCallException;
use Closure;
use Mate\Container\DependencyInjection;
use Mate\Http\HttpMethod;
use Mate\Http\HttpNotFoundException;
use Mate\Http\Request;
use Mate\Http\Response;

/**
 * HTTP router.
 */
class Router extends RoutingBase
{
    /**
     * HTTP routes.
     *
     * @var array<string, Route[]>
     */
    protected array $routes = [];

    /**
     * Route groups.
     *
     * @var array<string, RouteGroup>
     */
    protected array $groups = [];

    /**
     * Create a new router.
     */
    public function __construct()
    {
        foreach (HttpMethod::cases() as $method) {
            $this->routes[$method->value] = [];
        }
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
        foreach ($this->routes[$request->method()->value] as $route) {
            if ($route->matches($request->uri())) {
                return $route;
            }
        }

        foreach ($this->groups as $group) {
            if ($group->matches($request->uri())) {
                return $group->resolveRoute($request);
            }
        }

        throw new HttpNotFoundException('No route found for ' . $request->uri());
    }

    public function resolve(Request $request): Response
    {
        $route = $this->resolveRoute($request);
        $request->setRoute($route);
        $action = $route->action();
        $model = $route->getModel();

        $middlewares = $route->middlewares();

        if (is_array($action)) {
            $controller = new $action[0]();
            $action[0] = $controller;
            $middlewares = array_merge($middlewares, $controller->middlewares());
        }

        $params = DependencyInjection::resolveParameters($action, $request->routeParameters());

        if ($model && is_array($params) && is_null($params[0])) {

            $params[0] = new $model;
        }

        return $this->runMiddlewares(
            $request,
            $middlewares,
            fn () => call_user_func($action, ...$params)
        );
    }

    protected function runMiddlewares(Request $request, array $middlewares, $target): Response
    {
        if (count($middlewares) == 0) {
            return $target();
        }

        return $middlewares[0]->handle(
            $request,
            fn ($request) => $this->runMiddlewares($request, array_slice($middlewares, 1), $target)
        );
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
        } else {
            $modelInject = $this->isImplicitInjection($uri);
            if ($modelInject) {
                
                $route->model($route, $modelInject);
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
     * Create a new route group with the given `$prefix`.
     *
     * @param string $prefix
     * @param Closure $callback
     * @return RouteGroup
     */
    public function group(array $attributes, Closure $callback): RouteGroup
    {
        $group = new RouteGroup($attributes['prefix']);
        $callback($group);
        $this->groups[] = $group;

        return $group;
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
