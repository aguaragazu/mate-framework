<?php

namespace Mate\Database\Concerns;

use Mate\Database\Database as Builder;
use Mate\Database\Database;
use Mate\Database\Model;
use Mate\Database\Relations\BelongsTo;
use Mate\Database\Relations\HasMany;
use Mate\Database\Relations\HasOne;
use Mate\Support\Str;

trait HasRelationships
{
    protected $relations = [];

    protected static $relationResolvers = [];

    /**
     * Get the dynamic relation resolver if defined or inherited, or return null.
     *
     * @param  string  $class
     * @param  string  $key
     * @return mixed
     */
    public function relationResolver($class, $key)
    {
        if ($resolver = static::$relationResolvers[$class][$key] ?? null) {
            return $resolver;
        }

        if ($parent = get_parent_class($class)) {
            return $this->relationResolver($parent, $key);
        }

        return null;
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \Mate\Database\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOne relationship.
     *
     * @param  \Mate\Database\Database  $query
     * @param  \Mate\Database\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Mate\Database\Relations\HasOne
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \Mate\Database\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    /**
     * Instantiate a new HasMany relationship.
     *
     * @param  Mate\Database\Builder  $query
     * @param  Mate\Database\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return Mate\Database\Relations\HasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $ownerKey
     * @param  string|null  $relation
     * @return \Mate\Database\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation) . '_' . $instance->getKeyName();
        }

        // Once we have the foreign key names we'll just create a new Eloquent query
        // for the related models and return the relationship instance which will
        // actually be responsible for retrieving and hydrating every relation.
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $relation
        );
    }

    /**
     * Instantiate a new BelongsTo relationship.
     *
     * @param  \Mate\Database\Database  $query
     * @param  \Mate\Database\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return \Mate\Database\Relations\BelongsTo
     */
    protected function newBelongsTo(Database $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Create a new model instance for a related model.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (!$instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param  string  $related
     * @param  \Mate\Database\Model|null  $instance
     * @return string
     */
    public function joiningTable($related, $instance = null)
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $segments = [
            $instance ? $instance->joiningTableSegment()
                      : Str::snake(class_basename($related)),
            $this->joiningTableSegment(),
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($segments);

        return strtolower(implode('_', $segments));
    }

    /**
     * Get all the loaded relations for the instance.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the given relationship on the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Unset a loaded relationship.
     *
     * @param  string  $relation
     * @return $this
     */
    public function unsetRelation($relation)
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Duplicate the instance and unset all the loaded relations.
     *
     * @return $this
     */
    public function withoutRelations()
    {
        $model = clone $this;

        return $model->unsetRelations();
    }

    /**
     * Unset all the loaded relations for the instance.
     *
     * @return $this
     */
    public function unsetRelations()
    {
        $this->relations = [];

        return $this;
    }
}
