<?php

namespace Mate\Routing;

use Closure;
use BadMethodCallException;
use InvalidArgumentException;

/**
 * @method \Mate\Routing\Router get(string $uri, \Closure|array|string|null $action = null, string|null $model = null)
 * @method \Mate\Routing\Router post(string $uri, \Closure|array|string|null $action = null, string|null $model = null)
 * @method \Mate\Routing\Router put(string $uri, \Closure|array|string|null $action = null, string|null $model = null)
 * @method \Mate\Routing\Router delete(string $uri, \Closure|array|string|null $action = null, string|null $model = null)
 * @method \Mate\Routing\Route name(string $name)
 * @method \Mate\Routing\Route namespace(string|null $namespace)
 * @method \Mate\Routing\Route prefix(string $prefix)
 * @method \Mate\Routing\Route middleware(string $middleware)
 * @method \Mate\Routing\Route action()
 * @method \Mate\Routing\Route model($model = null)
 * @method \Mate\Routing\Route atributte(string $routesDirectory)
 * @method \Mate\Routing\Route matches(string $uri)
 * @method \Mate\Routing\Route hasParameters()
 * @method \Mate\Routing\Route parseParameters(string $uri)
 * @method \Mate\Routing\Route group(Closure $callback)
 * @method \Mate\Routing\Route registerRoute($method, $uri, $action = null, $model = null)
 * 
 */
class Route extends RoutingBase

{
    /**
     * Router instance
     * 
     * @var \Mate\Routing\Router
     */
    protected $router;

    /**
     * Route URI.
     *
     * @var string
     */
    public string $uri;

    /**
     * Route action.
     *
     * @var Closure|array
     */
    public $action;

    /**
     * Route name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Route namespace.
     *
     * @var string|null
     */
    public ?string $namespace = null;

    /**
     * Route prefix.
     *
     * @var string|null
     */
    public ?string $prefix = null;

    /**
     * Regular expression used to match incoming requests URIs.
     *
     * @var string
     */
    protected string $regex;

    /**
     * Route parameter names.
     *
     * @var string[]
     */
    public array $parameters;

    /**
     * Route middlewares.
     *
     * @var array
     */
    protected array $middlewares = [];

    protected array $attributes = [];

    protected $model;

    /**
     * Create a new route.
     *
     * @param string $uri
     * @param Closure|array $action
     */
    public function __construct(string $uri, $action, ?string $model = null)
    {
        $this->uri = $uri;
        $this->action = $action;
        $this->regex = preg_replace('/\{([a-zA-Z]+)\}/', '([a-zA-Z0-9]+)', $uri);
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $parameters);
        $this->parameters = $parameters[1];
        $this->model = $model;
        $this->router = app()->router;
    }

    public function attribute($key, $value)
    {
        if (!in_array($key, $this->attributesAllowed)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        if ($key === 'middleware') {
            foreach ($value as $index => $middleware) {
                $value[$index] = (string) $middleware;
            }
        }

        $attribute = $this->attributesAllowed[$key];

        $this->{$attribute} = $value;

        $this->attributes[$attribute] = $value;

        return $this;
    }
    /**
     * Get the route URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route action.
     *
     * @return Closure|array
     */
    public function action()
    {
        return $this->action;
    }


    /**
     * Set the route name.
     *
     * @param Route $route
     * @param string $name
     * @return Route
     */
    public static function name(Route $route, string $name): Route
    {
        $route->name = $name;
        return $route;
    }

    /**
     * Set the route namespace.
     *
     * @param string $namespace
     * @return Route
     */
    public static function namespace(Route $route, string $namespace = null): self
    {
        $route->namespace = $namespace;
        return $route;
    }

    /**
     * Set the route prefix.
     *
     * @param string $prefix
     * @return Route
     */
    public static function prefix(Route $route, string $prefix = null): self
    {
        $route->prefix = $prefix;
        return $route;
    }

    /**
     * Get the route middlewares.
     *
     * @return array
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Add a middleware to the route.
     *
     * @param string $middleware
     * @return Route
     */
    public static function middleware(Route $route, string $middleware = null): self
    {
        $route->middlewares[] = $middleware;
        return $route;
    }

    /**
     * Set the route model.
     *
     * @param string $model
     * @return Route
     */
    public static function model(Route $route, string $model = null): Route
    {
        $route->model = $model;
        return $route;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Check if the route matches the given `$uri`.
     *
     * @param string $uri
     * @return bool
     */
    public function matches(string $uri): bool
    {
        // return $this->uri === $uri;
        return preg_match("#^$this->regex/?$#", $uri);
    }

    /**
     * Check if this route has variable paramaters in its definition.
     *
     * @return boolean
     */
    public function hasParameters(): bool
    {
        return count($this->parameters) > 0;
    }

    /**
     * Get the key-value pairs from the `$uri` as defined by this route.
     *
     * @param string $uri
     * @return array
     */
    public function parseParameters(string $uri): array
    {
        preg_match("#^$this->regex$#", $uri, $arguments);

        return array_combine($this->parameters, array_slice($arguments, 1));
    }

    public static function load(string $routesDirectory)
    {
        foreach (glob("$routesDirectory/*.php") as $routes) {
            require_once $routes;
        }
    }

    public function group(Closure $callback): RouteGroup
    {
        return $this->router->group($this->attributes, $callback);
    }

    /**
     * Register a new route with the router.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @param  string|null  $model
     * @return \Mate\Routing\Route
     */
    protected function registerRoute($method, $uri, $action = null, $model = null)
    {
        if (!is_array($action)) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }

        return $this->router->{$method}($uri, $action, $model);
    }

    public static function get($uri, $action = null, $model = null)
    {
        
        return app()->router->get($uri, $action, $model);
    }

    public static function post($uri, $action = null, $model = null)
    {
        return app()->router->post($uri, $action, $model);
    }

    public static function delete($uri, $action = null, $model = null)
    {
        return app()->router->delete($uri, $action, $model);
    }

    public static function put($uri, $action = null, $model = null)
    {
        return app()->router->put($uri, $action, $model);
    }

    /**
     * Dynamically handle calls into the route registrar.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Mate\Routing\Route|$this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->methodsAllowed)) {
            return $this->registerRoute($method, ...$parameters);
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
}
