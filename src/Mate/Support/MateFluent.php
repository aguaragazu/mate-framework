<?php

namespace Mate\Support;

class MateFluent
{
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __call($method, $parameters)
    {
        $this->attributes[$method] = $parameters[0] ?? true;
        return $this;
    }


}