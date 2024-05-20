<?php

namespace Mate\Collections;

use ArrayAccess;
// use Countable;
// use IteratorAggregate;
// use JsonSerializable;
// use Mate\Contracts\Support\Jsonable;
use Mate\Support\Arr;
use Mate\Support\Arrayable;
use Stringable;
// use UnitEnum;
use Mate\Collections\Traits\EnumeratesValues;
use Mate\Traits\Macroable;
use stdClass;


class Collection implements ArrayAccess, Enumerable
{
    use EnumeratesValues, Macroable; 

    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return static<int, mixed>
     */
    public function flatten($depth = INF)
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  \Mate\Contracts\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback)
    {
        return new static(Arr::mapWithKeys($this->items, $callback));
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array<TKey, array<TItemKey, TItemValue>>
     */
    public function __toArray(): array
    {
        return array_map(static fn(Arrayable $item): array => $item->toArray(), $this->items);
    }

    /**
     * @return array<TKey, array<TItemKey, TItemValue>>
     */
    public function toArray(): array
    {
        return $this->__toArray();
    }

    /**
     * @return array<TKey, array<TItemKey, TItemValue>>
     */
    public function jsonSerialize(): array
    {
        return $this->__toArray();
    }

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all()
    {
        return $this->items;
    }
    
    /**
     * Collapse the collection of items into a single array.
     *
     * @return static<int, mixed>
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @return static
     */
    public function filter(?callable $callback = null)
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Get the first item from the collection passing the given truth test.
     *
     * @template TFirstDefault
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param  TFirstDefault|(\Closure(): TFirstDefault)  $default
     * @return TValue|TFirstDefault
     */
    public function first(?callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $default);
    }

    /**
     * Flip the items in the collection.
     *
     * @return static<TValue, TKey>
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Get an item from the collection by key.
     *
     * @template TGetDefault
     *
     * @param  TKey  $key
     * @param  TGetDefault|(\Closure(): TGetDefault)  $default
     * @return TValue|TGetDefault
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return value($default);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  TKey|array<array-key, TKey>  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (! array_key_exists($value, $this->items)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in the collection.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function hasAny($key)
    {
        if ($this->isEmpty()) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->has($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEempty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get the last item from the collection.
     *
     * @template TLastDefault
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param  TLastDefault|(\Closure(): TLastDefault)  $default
     * @return TValue|TLastDefault
     */
    public function last(?callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get and remove the last N items from the collection.
     *
     * @param  int  $count
     * @return static<int, TValue>|TValue|null
     */
    public function pop($count = 1)
    {
        if ($count === 1) {
            return array_pop($this->items);
        }

        if ($this->isEmpty()) {
            return new static;
        }

        $results = [];

        $collectionCount = $this->count();

        foreach (range(1, min($count, $collectionCount)) as $item) {
            array_push($results, array_pop($this->items));
        }

        return new static($results);
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }

        $count = $this->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $this->last();
        }

        $collection = new static($this->items);

        $finalItem = $collection->pop();

        return $collection->implode($glue).$finalGlue.$finalItem;
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
     * Concatenate values of a given key as a string.
     *
     * @param  callable|string  $value
     * @param  string|null  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        if ($this->useAsCallable($value)) {
            return implode($glue ?? '', $this->map($value)->all());
        }

        $first = $this->first();

        if (is_array($first) || (is_object($first) && ! $first instanceof Stringable)) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }

        return implode($value ?? '', $this->items);
    }

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback)
    {
        return new static(Arr::map($this->items, $callback));
    }

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToDictionaryKey of array-key
     * @template TMapToDictionaryValue
     *
     * @param  callable(TValue, TKey): array<TMapToDictionaryKey, TMapToDictionaryValue>  $callback
     * @return static<TMapToDictionaryKey, array<int, TMapToDictionaryValue>>
     */
    public function mapToDictionary(callable $callback)
    {
        $dictionary = [];

        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);

            $key = key($pair);

            $value = reset($pair);

            if (! isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $value;
        }

        return new static($dictionary);
    }

    /**
     * Get the values of a given key.
     *
     * @param  string|int|array<array-key, string>  $value
     * @param  string|null  $key
     * @return static<array-key, mixed>
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Convert the array into a query string.
     *
     * @param  array  $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (func_num_args() == 2) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  \Mate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Push all of the given items onto the collection.
     *
     * @template TConcatKey of array-key
     * @template TConcatValue
     *
     * @param  iterable<TConcatKey, TConcatValue>  $source
     * @return static<TKey|TConcatKey, TValue|TConcatValue>
     */
    public function concat($source)
    {
        $result = new static();

        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @param  TValue  ...$values
     * @return $this
     */
    public function push(...$values)
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Get and remove an item from the collection.
     *
     * @template TPullDefault
     *
     * @param  TKey  $key
     * @param  TPullDefault|(\Closure(): TPullDefault)  $default
     * @return TValue|TPullDefault
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  TKey  $key
     * @param  TValue  $value
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param  TValue|(callable(TValue,TKey): bool)  $value
     * @param  bool  $strict
     * @return TKey|false
     */
    public function search($value, $strict = false)
    {
        if (! $this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }

        return false;
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
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @param  (callable(TValue, TKey): bool)|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return TValue
     *
     * @throws \Mate\Support\ItemNotFoundException
     */
    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $filter = func_num_args() > 1
            ? $this->operatorForWhere(...func_get_args())
            : $key;

        $placeholder = new stdClass();

        $item = $this->first($filter, $placeholder);

        if ($item === $placeholder) {
            throw new ItemNotFoundException();
        }

        return $item;
    }

    /**
     * Create a collection with the given range.
     *
     * @param  int  $from
     * @param  int  $to
     * @return static<int, int>
     */
    public static function range($from, $to)
    {
        return new static(range($from, $to));
    }

    public function contains($key, $operator = null, $value = null): bool
    {
        return Arr::has($this->items, $key);
    }

    public function dd(...$args)
    {
        dd($this->items, ...$args);
    }

    public function dump(...$args)
    {
        $this->dd(...$args); // Delegate to existing dd method
    }

    public function each(callable $callback): Collection
    {
        foreach ($this->items as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    public function except($keys)
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_array($keys)) {
            $keys = func_get_args();
        }

        return new static(Arr::except($this->items, $keys));
    }

    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (! $this->useAsCallable($groupBy) && is_array($groupBy)) {
            $nextGroups = $groupBy;

            $groupBy = array_shift($nextGroups);
        }

        $groupBy = $this->valueRetriever($groupBy);

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (! is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                $groupKey = match (true) {
                    is_bool($groupKey) => (int) $groupKey,
                    $groupKey instanceof \Stringable => (string) $groupKey,
                    default => $groupKey,
                };

                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }

                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        $result = new static($results);

        if (! empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }

        return $result;
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static<int, TKey>
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Add an item to the collection.
     *
     * @param  TValue  $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * @return \Mate\Support\Collection<TKey, TValue>
     */
    public function toBase()
    {
        return new self($this);
    }
}