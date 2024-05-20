<?php

namespace Mate\Database\Relations;

class HasMany implements Relation {
  protected $related;
  protected $foreignKey;
  protected $localKey;

  public function __construct($related, $foreignKey, $localKey) {
      $this->related = $related;
      $this->foreignKey = $foreignKey;
      $this->localKey = $localKey;
  }

  public function getResults() {
      return $this->related->where($this->foreignKey, '=', $this->localKey)->get();
  }
}