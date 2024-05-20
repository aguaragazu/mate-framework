<?php

namespace Mate\Collections\Traits;

use BackedEnum;
use JsonSerializable;
use Mate\Collections\Enumerable;
use Mate\Contracts\Support\Arrayable;
use Mate\Contracts\Support\Jsonable;
use Mate\Support\Arr;
use Mate\Conditionable\Traits\Conditionable;
use Traversable;
use UnitEnum;

trait EnumeratesValues
{
    use Conditionable;
  /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param  \Mate\Contracts\Support\Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null  $items
     * @return static<TMakeKey, TMakeValue>
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @template TWrapValue
     *
     * @param  iterable<array-key, TWrapValue>|TWrapValue  $value
     * @return static<array-key, TWrapValue>
     */
    public static function wrap($value)
    {
        return $value instanceof Enumerable
            ? new static($value)
            : new static(Arr::wrap($value));
    }

  /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array<TKey, TValue>
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }

        return match (true) {
            $items instanceof Enumerable => $items->all(),
            $items instanceof Arrayable => $items->toArray(),
            $items instanceof Traversable => iterator_to_array($items),
            $items instanceof Jsonable => json_decode($items->toJson(), true),
            $items instanceof JsonSerializable => (array) $items->jsonSerialize(),
            $items instanceof UnitEnum => [$items],
            default => (array) $items,
        };
    }

  /**
     * Filter items by the given key value pair.
     *
     * @param  string  $key
     * @param  \Mate\Contracts\Support\Arrayable|iterable  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(fn ($item) => in_array(data_get($item, $key), $values, $strict));
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<TKey, mixed>
     */
    public function toArray()
    {
        return $this->map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value)->all();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<TKey, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            }

            return $value;
        }, $this->all());
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  callable|string|null  $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return fn ($item) => data_get($item, $value);
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param  callable|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return TValue|null
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get an operator checker callback.
     *
     * @param  callable|string  $key
     * @param  string|null  $operator
     * @param  mixed  $value
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
                case '<=>': return $retrieved <=> $value;
            }
        };
    }

    /**
     * Apply the callback if the collection is empty.
     *
     * @template TWhenEmptyReturnType
     *
     * @param  (callable($this): TWhenEmptyReturnType)  $callback
     * @param  (callable($this): TWhenEmptyReturnType)|null  $default
     * @return $this|TWhenEmptyReturnType
     */
    public function whenEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the collection is not empty.
     *
     * @template TWhenNotEmptyReturnType
     *
     * @param  callable($this): TWhenNotEmptyReturnType  $callback
     * @param  (callable($this): TWhenNotEmptyReturnType)|null  $default
     * @return $this|TWhenNotEmptyReturnType
     */
    public function whenNotEmpty(callable $callback, callable $default = null)
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    /**
     * Create a new instance with no items.
     *
     * @return static
     */
    public static function empty()
    {
        return new static([]);
    }

    /**
     * Map the values into a new class.
     *
     * @template TMapIntoValue
     *
     * @param  class-string<TMapIntoValue>  $class
     * @return static<TKey, TMapIntoValue>
     */
    public function mapInto($class)
    {
        if (is_subclass_of($class, BackedEnum::class)) {
            return $this->map(fn ($value, $key) => $class::from($value));
        }

        return $this->map(fn ($value, $key) => new $class($value, $key));
    }
}