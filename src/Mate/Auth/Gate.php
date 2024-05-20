<?php

namespace Mate\Auth;

use App\Models\User;

abstract class Gate
{
    public function __construct(protected User $user)
    {
        //
    }

    abstract public function authorize(string $ability, array $arguments = []): bool;
}