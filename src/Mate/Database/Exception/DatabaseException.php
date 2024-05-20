<?php

namespace Mate\Database\Exception;

class DatabaseException extends \Exception
{
    public const ERR_MSG_CONNECTION_FAIL = "[Database] Database Connection Failed";
    public const ERR_MSQ_BAD_REQUEST = "Bad Request";
    public const ERR_CODE_BAD_REQUEST = 400;
    public const ERR_MSG_INVALID_ARGUMENTS = "[Database] Invalid Arguments";
    public const ERR_MSG_INVALID_DRIVER = "[Database] Invalid Driver";
    public const ERR_MSG_INVALID_PROTOCOL = "[Database] Invalid Protocol";
}
