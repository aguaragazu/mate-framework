<?php

namespace Mate\Validation\Rules;

class Min implements ValidationRule
{
    protected int $minLength;

    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;
    }

    public function message(): string
    {
        return "The :attribute must be at least {$this->minLength} characters.";
    }

    public function isValid(string $field, array $data): bool
    {
        if (!isset($data[$field])) {
            return false;
        }
        
        return strlen($data[$field]) >= $this->minLength;
    }
}