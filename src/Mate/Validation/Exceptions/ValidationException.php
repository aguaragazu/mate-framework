<?php

namespace Mate\Validation\Exceptions;

use Mate\Exceptions\MateException;

class ValidationException extends MateException
{
    public function __construct(protected array $errors)
    {
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
