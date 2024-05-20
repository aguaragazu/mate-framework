<?php

namespace Mate\Support\Facades;

use Mate\Routing\Route as RouteClass;
use Mate\Support\Facades\Facade;

/**
 * @method static \Mate\Routing\Router get(string $uri, array|string|callable|null $action = null)
 * @method static \Mate\Routing\Router post(string $uri, array|string|callable|null $action = null)
 * @method static \Mate\Routing\Router put(string $uri, array|string|callable|null $action = null)
 * @method static \Mate\Routing\Router patch(string $uri, array|string|callable|null $action = null)
 * @method static \Mate\Routing\Router delete(string $uri, array|string|callable|null $action = null)
 * @method static \Mate\Routing\Route attribute(string $key, mixed $value)
 * @method static \Mate\Routing\Route name(string $value)
 * @method static \Mate\Routing\Route namespace(string|null $value)
 * @method static \Mate\Routing\Route prefix(string $prefix)
 * @method static \Mate\Routing\Route action()
 * @method static \Mate\Routing\Route model($model = null)
 * @method static \Mate\Routing\Route atributte(string $routesDirectory)
 * @method static \Mate\Routing\Route matches(string $uri)
 * @method static \Mate\Routing\Route hasParameters()
 * @method static \Mate\Routing\Route parseParameters(string $uri)
 * @method static \Mate\Routing\Route group(Closure $callback)
 * @method static \Mate\Routing\Route registerRoute($method, $uri, $action = null, $model = null)
 * 
 */
class Route extends Facade
{
    /**
     * Get the registered name of the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RouteClass::class;
    }
}
