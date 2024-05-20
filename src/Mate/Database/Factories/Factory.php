<?php

namespace Mate\Database\Factories;

use Mate\Database\Database;

abstract class Factory {
  use WithFaker;

  protected $model;
  protected $count = 1;

  public function __construct() {
      $this->setupFaker();
  }

  abstract public function definition();

  public function create($attributes = [], $count = null) {
      $count = $count ?? $this->count;
      $instances = [];

      for ($i = 0; $i < $count; $i++) {
          $defaults = $this->definition();
          $attributes = array_merge($defaults, $attributes);

          foreach ($attributes as $key => $value) {
              if (is_callable($value)) {
                  $attributes[$key] = $value($this->faker);
              }
          }

          // Aquí asumimos que tienes una función para insertar en la DB
          $keys = implode(", ", array_keys($attributes));
          $values = implode("', '", array_values($attributes));
          $sql = "INSERT INTO {$this->model} ($keys) VALUES ('$values');";
          Database::statement($sql);
          $instances[] = $attributes;
      }

      return count($instances) === 1 ? $instances[0] : $instances;
  }

  public static function forModel($model, $count = 1) {
      $factory = new static();
      $factory->model = $model;
      $factory->count = $count;
      return $factory;
  }
}