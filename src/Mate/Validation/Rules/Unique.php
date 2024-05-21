<?php

namespace Mate\Validation\Rules;

use Mate\Database\Model;

class Unique implements ValidationRule
{
    protected string $table;
    protected string $column;

    public function __construct(string $table, string $column = 'email')
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function message(): string
    {
        return "{$this->column} already exists in {$this->table}";
    }

    public function isValid(string $field, array $data): bool
    {
        if (!isset($data[$field])) {
            return false;
        }

        $value = $data[$field];
        $modelClass = Model::resolveModelClass($this->table);
        
        return !$modelClass::firstWhere($this->column, $value);
    }
}
