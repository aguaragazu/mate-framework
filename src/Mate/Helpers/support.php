<?php

use Mate\Contracts\Support\DeferringDisplayableValue;
use Mate\Contracts\Support\Htmlable;
use Mate\Support\MateTapProxy;
use Mate\Support\Stringable;

if (!function_exists('tap')) {
  /**
   * Call the given Closure with the given value then return the value.
   *
   * @param  mixed  $value
   * @param  callable|null  $callback
   * @return mixed
   */
  function tap($value, $callback = null)
  {
    if (is_callable($callback)) {
      $callback($value);
      return $value;
    }

    return new MateTapProxy($value);
  }
}

if (! function_exists('class_basename')) {
  /**
   * Get the class "basename" of the given object / class.
   *
   * @param  string|object  $class
   * @return string
   */
  function class_basename($class)
  {
      $class = is_object($class) ? get_class($class) : $class;

      return basename(str_replace('\\', '/', $class));
  }
}

if (! function_exists('with')) {
  /**
   * Return the given value, optionally passed through the given callback.
   *
   * @template TValue
   * @template TReturn
   *
   * @param  TValue  $value
   * @param  (callable(TValue): (TReturn))|null  $callback
   * @return ($callback is null ? TValue : TReturn)
   */
  function with($value, ?callable $callback = null)
  {
      return is_null($callback) ? $value : $callback($value);
  }
}

if (! function_exists('e')) {
  /**
   * Encode HTML special characters in a string.
   *
   * @param  \Mate\Contracts\Support\DeferringDisplayableValue|\Mate\Contracts\Support\Htmlable|\BackedEnum|string|null  $value
   * @param  bool  $doubleEncode
   * @return string
   */
  function e($value, $doubleEncode = true)
  {
      if ($value instanceof DeferringDisplayableValue) {
          $value = $value->resolveDisplayableValue();
      }

      if ($value instanceof Htmlable) {
          return $value->toHtml();
      }

      if ($value instanceof BackedEnum) {
          $value = $value->value;
      }

      return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
  }
}

  if (! function_exists('transform')) {
    /**
     * Transform the given value if it is present.
     *
     * @template TValue of mixed
     * @template TReturn of mixed
     * @template TDefault of mixed
     *
     * @param  TValue  $value
     * @param  callable(TValue): TReturn  $callback
     * @param  TDefault|callable(TValue): TDefault|null  $default
     * @return ($value is empty ? ($default is null ? null : TDefault) : TReturn)
     */
    function transform($value, callable $callback, $default = null)
    {
        if (filled($value)) {
            return $callback($value);
        }

        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }
}

if (! function_exists('filled')) {
  /**
   * Determine if a value is "filled".
   *
   * @param  mixed  $value
   * @return bool
   */
  function filled($value)
  {
      return ! blank($value);
  }
}

if (! function_exists('blank')) {
  /**
   * Determine if the given value is "blank".
   *
   * @param  mixed  $value
   * @return bool
   */
  function blank($value)
  {
      if (is_null($value)) {
          return true;
      }

      if (is_string($value)) {
          return trim($value) === '';
      }

      if (is_numeric($value) || is_bool($value)) {
          return false;
      }

      if ($value instanceof Countable) {
          return count($value) === 0;
      }

      if ($value instanceof Stringable) {
          return trim((string) $value) === '';
      }

      return empty($value);
  }
}