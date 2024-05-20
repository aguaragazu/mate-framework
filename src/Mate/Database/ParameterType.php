<?php

namespace Mate\Database;

class ParameterType
{
    const STR = \PDO::PARAM_STR;
    const INT = \PDO::PARAM_INT;
    const BOOL = \PDO::PARAM_BOOL;
    const NULL = \PDO::PARAM_NULL;
}