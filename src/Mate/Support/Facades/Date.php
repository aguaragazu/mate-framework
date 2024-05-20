<?php

namespace Mate\Support\Facades;

use Mate\Support\Date as SupportDate;

/**
 * @method static \Mate\Support\Date now()
 * @method static string format($format)
 */
class Date extends Facade
{

  const DEFAULT_FACADE = SupportDate::class;
  /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'date';
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (! isset(static::$resolvedInstance[$name])) {
            $class = static::DEFAULT_FACADE;

            static::swap(new $class);
        }

        return parent::resolveFacadeInstance($name);
    }

}