<?php

namespace Mate\Support;

use ArgumentCountError;
use ArrayAccess;
use Mate\Collections\Collection;
use Mate\Collections\Enumerable;

class Arr
{
    /**
     * Envuelve un valor en un arreglo si no lo está.
     *
     * @param mixed $value El valor a envolver.
     * @return array El valor como arreglo.
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Obtiene un valor de un arreglo de manera segura.
     *
     * @param array $array El arreglo a buscar.
     * @param string|int|null $key La clave del valor a obtener.
     * @param mixed $default El valor por defecto si la clave no existe.
     * @return mixed El valor de la clave o el valor por defecto.
     */
    public static function get(array $array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $default;
    }

    /**
     * Agrega un elemento a un arreglo asociativo.
     *
     * @param array $array El arreglo asociativo.
     * @param string $key La clave del elemento.
     * @param mixed $value El valor del elemento.
     * @return array El arreglo actualizado.
     */
    public static function add(array $array, $key, $value)
    {
        $array[$key] = $value;
        return $array;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Obtiene el primer elemento de un arreglo.
     *
     * @param array $array El arreglo a buscar.
     * @param mixed $default El valor por defecto si el arreglo está vacío.
     * @return mixed El primer elemento del arreglo o el valor por defecto.
     */
    public static function first(array $array, $default = null)
    {
        if (empty($array)) {
            return $default;
        }

        return reset($array);
    }

    /**
     * Obtiene el último elemento de un arreglo.
     *
     * @param array $array El arreglo a buscar.
     * @param mixed $default El valor por defecto si el arreglo está vacío.
     * @return mixed El último elemento del arreglo o el valor por defecto.
     */
    public static function last(array $array, $default = null)
    {
        if (empty($array)) {
            return $default;
        }

        return end($array);
    }

    /**
     * Extrae una columna de valores de un arreglo asociativo.
     *
     * @param array $array El arreglo asociativo.
     * @param string $key La clave de la columna a extraer.
     * @return array La columna de valores extraída.
     */
    public static function pluck(array $array, $key)
    {
        $values = [];
        foreach ($array as $item) {
            if (isset($item[$key])) {
                $values[] = $item[$key];
            }
        }

        return $values;
    }

    /**
     * Combina dos arreglos.
     *
     * @param array $array1 El primer arreglo.
     * @param array $array2 El segundo arreglo.
     * @return array El arreglo combinado.
     */
    public static function merge(array $array1, array $array2)
    {
        return array_merge($array1, $array2);
    }

    /**
     * Obtiene la diferencia entre dos arreglos.
     *
     * @param array $array1 El primer arreglo.
     * @param array $array2 El segundo arreglo.
     * @return array La diferencia entre los arreglos.
     */
    public static function diff(array $array1, array $array2)
    {
        return array_diff($array1, $array2);
    }

    /**
     * Filtra un arreglo según una función de callback.
     *
     * @param array $array El arreglo a filtrar.
     * @param callable $callback La función de callback.
     * @return array El arreglo filtrado.
     */
    public static function filter(array $array, callable $callback)
    {
        return array_filter($array, $callback);
    }

    /**
     * Cuenta el número de elementos en un arreglo.
     *
     * @param array $array El arreglo a contar.
     * @return int El número de elementos.
     */
    public static function count(array $array)
    {
        return count($array);
    }

    /**
     * Verifica si un valor es un arreglo usando ArrayAccess.
     *
     * @param mixed $value El valor a verificar.
     * @return bool True si el valor es un arreglo, False en caso contrario.
     */
    public static function isArray($value)
    {
        return $value instanceof ArrayAccess;
    }

    /**
     * Aplica una función de callback a cada elemento de un arreglo y devuelve un nuevo arreglo con los resultados.
     *
     * @param array $array El arreglo a mapear.
     * @param callable $callback La función de callback.
     * @return array El nuevo arreglo con los resultados del mapeo.
     */
    public static function map(array $array, callable $callback)
    {
        $keys = array_keys($array);

        try {
            $items = array_map($callback, $array, $keys);
        } catch (ArgumentCountError) {
            $items = array_map($callback, $array);
        }

        return array_combine($keys, $items);
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TKey
     * @template TValue
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  array<TKey, TValue>  $array
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return array
     */
    public static function mapWithKeys(array $array, callable $callback)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return $result;
    }

    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array  $array
     * @param  string|int  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string|int|float  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && static::accessible($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof Enumerable) {
            return $array->has($key);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        if (is_float($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $array);
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  iterable  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        $result = [];

        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : static::flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array  $array
     * @param  array|string|int|float  $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}