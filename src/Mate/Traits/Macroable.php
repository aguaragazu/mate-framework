<?php

namespace Mate\Traits;

use BadMethodCallException;
use Closure;

trait Macroable
{
    private static $macros = [];

    /**
     * Register a macro with a specific name and callback function.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function macro(string $name, callable $callback): void
    {
        static::$macros[$name] = $callback;
    }

    /**
     * Check if a macro exists for a given name.
     *
     * @param string $name
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Flush the existing macros.
     *
     * @return void
     */
    public static function flushMacros()
    {
        static::$macros = [];
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        if (! static::hasMacro($name)) {
            throw new BadMethodCallException(sprintf(
                'Macro %s::%s does not exist.', static::class, $name
            ));
        }

        $macro = static::$macros[$name];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$arguments);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }

}