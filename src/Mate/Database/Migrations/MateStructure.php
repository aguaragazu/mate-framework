<?php

namespace Mate\Database\Migrations;

class MateStructure
{
    public $columns = [];
    public $foreignKeys = [];
    public $indices = [];

    public function addColumn($name, $type)
    {
        $this->columns[$name] = new MateColumn(['type' => $type]);
        return $this->columns[$name];
    }

    public function string($name)
    {
        return $this->addColumn($name, 'string');
    }

    public function integer($name)
    {
        return $this->addColumn($name, 'integer');
    }

    public function timestamps()
    {
        $this->datetime('created_at');
        $this->datetime('updated_at');
        return $this;
    }

    public function datetime($column)
    {
        $this->columns[$column] = "DATETIME";
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function foreign($column, $references, $on, $onDelete = 'RESTRICT')
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $references,
            'on' => $on,
            'onDelete' => $onDelete
        ];
        return $this;
    }

    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    public function index($columns, $name = null)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $this->indices[] = [
            'columns' => $columns,
            'name' => $name ?: 'idx_' . implode('_', $columns)
        ];
        return $this;
    }

    public function getIndices()
    {
        return $this->indices;
    }

}
