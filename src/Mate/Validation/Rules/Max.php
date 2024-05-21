<?php

namespace Mate\Validation\Rules;

class Max implements ValidationRule
{
    protected int $maxLength;

    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function message(): string
    {
        return "The :attribute must be at max {$this->maxLength} characters.";
    }

    public function isValid(string $field, array $data): bool
    {
        if (!isset($data[$field])) {
            return false;
        }

        return strlen($data[$field]) <= $this->maxLength;
    }
}