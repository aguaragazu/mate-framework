<?php

namespace Mate\Database\Relations;

use Mate\Collection\Collection;
use Mate\Database\Database;
use Mate\Database\Model;

abstract class HasManyOrMany extends Relation
{
  use InteractsWithDictionary; 

  protected $foreignKeys;
  protected $localKey;

  public function __construct(Database $query, Model $parent, $foreignKeys, $localKey)
  {
    $this->foreignKeys = $foreignKeys;
    $this->localKey = $localKey;

    parent::__construct($query, $parent);
  }

  public function make(array $attributes = [])
  {
    return tap($this->relatedModel->newInstance($attributes), function ($instance) {
      $this->setForeignAttributesForCreate($instance);
    });
  }

  /**
   * Create and return an un-saved instance of the related models.
   *
   * @param  iterable  $records
   * @return \Mate\Collection\Collection
   */
  public function makeMany($records)
  {
    $instances = $this->relatedModel->newCollection();

    foreach ($records as $record) {
      $instances->push($this->make($record));
    }

    return $instances;
  }

  /**
   * Match the eagerly loaded results to their single parents.
   *
   * @param  array  $models
   * @param  \Mate\Collection\Collection  $results
   * @param  string  $relation
   * @return array
   */
  public function matchOne(array $models, Collection $results, $relation)
  {
    return $this->matchOneOrMany($models, $results, $relation, 'one');
  }

  /**
   * Match the eagerly loaded results to their many parents.
   *
   * @param  array  $models
   * @param  \Mate\Collection\Collection  $results
   * @param  string  $relation
   * @return array
   */
  public function matchMany(array $models, Collection $results, $relation)
  {
    return $this->matchOneOrMany($models, $results, $relation, 'many');
  }

  /**
   * Match the eagerly loaded results to their many parents.
   *
   * @param  array  $models
   * @param  \Mate\Collection\Collection  $results
   * @param  string  $relation
   * @param  string  $type
   * @return array
   */
  protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
  {
    $dictionary = $this->buildDictionary($results);

    // Once we have the dictionary we can simply spin through the parent models to
    // link them up with their children using the keyed dictionary to make the
    // matching very convenient and easy work. Then we'll just return them.
    foreach ($models as $model) {
      if (isset($dictionary[$key = $this->getDictionaryKey($model->getAttribute($this->localKey))])) {
        $model->setRelation(
          $relation,
          $this->getRelationValue($dictionary, $key, $type)
        );
      }
    }

    return $models;
  }

  /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Mate\Collection\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(function ($result) use ($foreign) {
            return [$this->getDictionaryKey($result->{$foreign}) => $result];
        })->all();
    }

  /**
   * Get the value of a relationship by one or many type.
   *
   * @param  array  $dictionary
   * @param  string  $key
   * @param  string  $type
   * @return mixed
   */
  protected function getRelationValue(array $dictionary, $key, $type)
  {
    $value = $dictionary[$key];

    return $type === 'one' ? reset($value) : $this->relatedModel->newCollection($value);
  }

  /**
   * Attach a model instance to the parent model.
   *
   * @param  \Mate\Database\Model  $model
   * @return \Mate\Database\Model|false
   */
  public function save(Model $model)
  {
    $this->setForeignAttributesForCreate($model);

    return $model->save() ? $model : false;
  }

  /**
   * Set the foreign ID for creating a related model.
   *
   * @param  \Mate\Database\Model  $model
   * @return void
   */
  protected function setForeignAttributesForCreate(Model $model)
  {
    $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
  }

  /**
   * Get the key value of the parent's local key.
   *
   * @return mixed
   */
  public function getParentKey()
  {
    return $this->parentModel->getAttribute($this->localKey);
  }

  /**
   * Get the fully qualified parentModel key name.
   *
   * @return string
   */
  public function getQualifiedParentKeyName()
  {
    return $this->parentModel->qualifyColumn($this->localKey);
  }

  /**
   * Get the plain foreign key.
   *
   * @return string
   */
  public function getForeignKeyName()
  {
    $segments = explode('.', $this->getQualifiedForeignKeyName());

    return end($segments);
  }

  /**
   * Get the foreign key for the relationship.
   *
   * @return string
   */
  public function getQualifiedForeignKeyName()
  {
    return $this->foreignKey;
  }

  /**
   * Get the local key for the relationship.
   *
   * @return string
   */
  public function getLocalKeyName()
  {
    return $this->localKey;
  }
}
