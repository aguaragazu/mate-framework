<?php

namespace Mate\Support\Facades;

class User extends Model
{
    protected static function getModelClass()
    {
        return \App\Models\User::class;
    }
}
