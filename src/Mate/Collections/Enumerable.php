<?php

namespace Mate\Collections;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Mate\Contracts\Support\Jsonable;
use Mate\Support\Arrayable;

interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
  /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param  \Mate\Support\Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null  $items
     * @return static<TMakeKey, TMakeValue>
     */
    public static function make($items = []);

    /**
     * Create a collection with the given range.
     *
     * @param  int  $from
     * @param  int  $to
     * @return static
     */
    public static function range($from, $to);

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @template TWrapValue
     *
     * @param  iterable<array-key, TWrapValue>|TWrapValue  $value
     * @return static<array-key, TWrapValue>
     */
    public static function wrap($value);

    /**
     * Create a new instance with no items.
     *
     * @return static
     */
    public static function empty();

    /**
     * Get all items in the enumerable.
     *
     * @return array
     */
    public function all();

    /**
     * Collapse the items into a single enumerable.
     *
     * @return static<int, mixed>
     */
    public function collapse();

    /**
     * Determine if an item exists in the enumerable.
     *
     * @param  (callable(TValue, TKey): bool)|TValue|string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null);

    /**
     * Dump the collection and end the script.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args);

    /**
     * Dump the collection.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args);

    /**
     * Get the items that are not present in the given items.
     *
     * @param  \Mate\Support\Arrayable<array-key, TValue>|iterable<array-key, TValue>  $items
     * @return static
     */
    public function diff($items);

    /**
     * Execute a callback over each item.
     *
     * @param  callable(TValue, TKey): mixed  $callback
     * @return $this
     */
    public function each(callable $callback);
    
    /**
     * Get all items except for those with the specified keys.
     *
     * @param  \Mate\Collection\Enumerable<array-key, TKey>|array<array-key, TKey>  $keys
     * @return static
     */
    public function except($keys);

    /**
     * Run a filter over each of the items.
     *
     * @param  (callable(TValue): bool)|null  $callback
     * @return static
     */
    public function filter(?callable $callback = null);

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TWhenReturnType as null
     *
     * @param  bool  $value
     * @param  (callable($this): TWhenReturnType)|null  $callback
     * @param  (callable($this): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     */
    public function when($value, ?callable $callback = null, ?callable $default = null);

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  TKey|array<array-key, TKey>  $key
     * @return bool
     */
    public function has($key);

    /**
     * Determine if any of the keys exist in the collection.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function hasAny($key);

    /**
     * Get the first item from the enumerable passing the given truth test.
     *
     * @template TFirstDefault
     *
     * @param  (callable(TValue,TKey): bool)|null  $callback
     * @param  TFirstDefault|(\Closure(): TFirstDefault)  $default
     * @return TValue|TFirstDefault
     */
    public function first(?callable $callback = null, $default = null);

    /**
     * Get the first item by the given key value pair.
     *
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return TValue|null
     */
    public function firstWhere($key, $operator = null, $value = null);

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF);

    /**
     * Flip the values with their keys.
     *
     * @return static<TValue, TKey>
     */
    public function flip();

    /**
     * Get an item from the collection by key.
     *
     * @template TGetDefault
     *
     * @param  TKey  $key
     * @param  TGetDefault|(\Closure(): TGetDefault)  $default
     * @return TValue|TGetDefault
     */
    public function get($key, $default = null);

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  (callable(TValue, TKey): array-key)|array|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static<array-key, static<array-key, TValue>>
     */
    public function groupBy($groupBy, $preserveKeys = false);

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  callable|string  $value
     * @param  string|null  $glue
     * @return string
     */
    public function implode($value, $glue = null);

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty();

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '');

    /**
     * Get the keys of the collection items.
     *
     * @return static<int, TKey>
     */
    public function keys();

    /**
     * Get the last item from the collection.
     *
     * @template TLastDefault
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param  TLastDefault|(\Closure(): TLastDefault)  $default
     * @return TValue|TLastDefault
     */
    public function last(?callable $callback = null, $default = null);

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback);

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
    public function mapToDictionary(callable $callback);

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
    public function firstOrFail($key = null, $operator = null, $value = null);

    /**
     * Map the values into a new class.
     *
     * @template TMapIntoValue
     *
     * @param  class-string<TMapIntoValue>  $class
     * @return static<TKey, TMapIntoValue>
     */
    public function mapInto($class);
}