<?php

namespace Mate\Database;

use Mate\Collections\Collection as BaseCollection;
use Mate\Contracts\Support\Arrayable;
use Mate\Support\Arr;

class Collection extends BaseCollection
{
  /**
     * Find a model in the collection by key.
     *
     * @template TFindDefault
     *
     * @param  mixed  $key
     * @param  TFindDefault  $default
     * @return static<TKey, TModel>|TModel|TFindDefault
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                return new static;
            }

            return $this->whereIn($this->first()->getKeyName(), $key);
        }

        return Arr::first($this->items, fn ($model) => $model->getKey() == $key, $default);
    }
}