<?php

namespace Mate\Support\Facades;

use Mate\Container\Container;

abstract class Facade
{
    /**
     * The resolved instance of the facade.
     *
     * @var mixed
     */
    protected static $resolvedInstance;

    /**
     * Get the registered name of the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Resolve the facade instance.
     *
     * @param string $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        // if (isset(static::$resolvedInstance[$name])) {
        //     return static::$resolvedInstance[$name];
        // }

        // if (is_null(static::$resolvedInstance[$name]) && !isset(static::$resolvedInstance[$name])) {            
        //     static::$resolvedInstance[$name] = Container::singleton($name);
        // }

        if(!is_null(static::$resolvedInstance) 
            && is_array(static::$resolvedInstance) 
            && isset(static::$resolvedInstance[$name])){

            return static::$resolvedInstance[$name];
        }
        
        static::$resolvedInstance[$name] = Container::singleton($name);
    }

    /**
     * Call a method on the facade instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new \RuntimeException('A facade root has not been set.');
        }

        // if (method_exists($instance, $method)) {
        //     return $instance->$method(...$parameters);
        // }
        
        return $instance->$method(...$parameters);

    }
}