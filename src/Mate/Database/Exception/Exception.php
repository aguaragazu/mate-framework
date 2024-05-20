<?php

namespace Mate\Database\Exception;

class Exception extends \PDOException
{
    private $sqlState;
    private $errorCode;

    public function __construct(
        string $message,
        int $code,
        \PDOException $previous = null,
        string $sqlState = null,
        int $errorCode = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->sqlState = $sqlState;
        $this->errorCode = $errorCode;
    }

    public function getSqlState(): string
    {
        return $this->sqlState;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}