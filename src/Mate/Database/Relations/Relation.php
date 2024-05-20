<?php

namespace Mate\Database\Relations;

use Mate\Database\Database;
use Mate\Database\Model;

abstract class Relation
{

  protected $query; // instancia Database
  protected $parentModel; // Modelo o clase padre
  protected $relatedModel; // Modelo o clase relacionada
  protected $foreignKey;   // Clave foránea en el modelo relacionado
  protected $localKey;     // Clave local en el modelo principal

  public function __construct(Database $query, Model $parent)
  {
    $this->query = $query;
    $this->parentModel = $parent;
    $this->relatedModel = $query->getModel();
  }

  /**
   * Execute the query as a "select" statement.
   *
   * @param  array  $columns
   * @return \Mate\Collection\Collection
   */
  public function get()
  {
    return $this->query->get();
  }

  /**
     * Get the parent model of the relation.
     *
     * @return \Mate\Database\Model
     */
    public function getParent()
    {
        return $this->parentModel;
    }

    /**
     * Get the related model of the relation.
     *
     * @return \Mate\Database\Model
     */
    public function getRelated()
    {
        return $this->relatedModel;
    }
}
