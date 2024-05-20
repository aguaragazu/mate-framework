<?php

namespace Mate\Database\Relations;

use Mate\Database\Model;

class HasOne extends HasManyOrMany {

    use SupportsDefaultModels;


    public function getResults() {

        if (is_null($this->getParentKey())) {
            return $this->getDefaultFor($this->parentModel);
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parentModel);
    
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  \Mate\Database\Model  $parent
     * @return \Mate\Database\Model
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->relatedModel->newInstance()->setAttribute(
            $this->getForeignKeyName(), $parent->{$this->localKey}
        );
    }

    /**
     * Get the value of the model's foreign key.
     *
     * @param  \Mate\Database\Model  $model
     * @return mixed
     */
    protected function getRelatedKeyFrom(Model $model)
    {
        return $model->getAttribute($this->getForeignKeyName());
    }
}