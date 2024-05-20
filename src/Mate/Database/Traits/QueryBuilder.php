<?php

namespace Mate\Database\Traits;

trait QueryBuilder
{

  /**
     * Execute the query and get the first result.
     *
     * @param  array|string  $columns
     * @return \Mate\Database\Model|object|static|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Pass the query to a given callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}