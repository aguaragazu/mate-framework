<?php

namespace Mate\Validation\Rules;


class Confirmed implements ValidationRule
{
    public function message(): string
    {
        return "The :attribute confirmation does not match.";
    }

    public function isValid(string $field, array $data): bool
    {
        if (!isset($data[$field])) {
            return false;
        }
        $confirmationField = "{$field}_confirmation";
        
        return isset($data[$confirmationField]) && $data[$field] === $data[$confirmationField];
    }
}
