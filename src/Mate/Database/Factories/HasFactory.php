<?php
namespace Mate\Database\Factories;

trait HasFactory {
  public static function factory($count = 1) {
      // Obtiene el nombre de la clase del modelo
      $modelClass = static::class;

      // Convierte el nombre del modelo a un nombre de fábrica
      // Ejemplo: User -> UserFactory
      $factoryClass = $modelClass . 'Factory';

      if (class_exists($factoryClass)) {
          return $factoryClass::forModel($modelClass, $count);
      }

      throw new \Exception("Factory class {$factoryClass} does not exist");
  }
}