<?php

namespace Mate\Validation\Exceptions;

use Mate\Exceptions\MateException;

class ValidationException extends MateException {
    public function __construct(private array $errors) {
        parent::__construct();
        $this->errors = $errors;
    }

    public function errors() {
        return $this->errors;
    }
}
