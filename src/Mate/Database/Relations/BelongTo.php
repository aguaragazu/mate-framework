<?php

namespace Mate\Database\Relations;

class BelongsTo extends Relation {
  
  public function getLocalKey() {
    // En BelongsTo, la clave local usualmente es la clave foránea en el modelo que tiene esta relación
      return $this->foreignKey;
  }

  public function getForeignKey() {
      // La clave foránea es la clave primaria del modelo relacionado
      return 'id'; // asumimos que el ID es siempre la clave primaria del modelo relacionado
  }

  public function getRelated() {
      return new $this->relatedModel;
  }

  public function getResults() {
    $modelInstance = $this->getRelated(); // Obtiene una nueva instancia del modelo relacionado
    return $modelInstance::where($this->getForeignKey(), '=', $this->getLocalKey())->first();
}
}