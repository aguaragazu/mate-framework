<?php

namespace Mate\Database\Exception;

use Mate\Database\Exception\RecordsNotFoundException;
use Mate\Support\Arr;

/**
 * @template TModel of \Mate\Database\Model
 */
class ModelNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected model.
     *
     * @var class-string<TModel>
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var array<int, int|string>
     */
    protected $ids;

    /**
     * Set the affected model and instance ids.
     *
     * @param  class-string<TModel>  $model
     * @param  array<int, int|string>|int|string  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected model.
     *
     * @return class-string<TModel>
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected model IDs.
     *
     * @return array<int, int|string>
     */
    public function getIds()
    {
        return $this->ids;
    }
}
