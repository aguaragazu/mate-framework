<?php

namespace Mate\Auth;

use App\Models\User;
use Mate\Database\Models\Model;

abstract class Policy
{
    public function __construct(protected User $user)
    {
        //
    }

    abstract public function view(User $user, Model $model): bool;

    abstract public function create(User $user, Model $model): bool;

    abstract public function update(User $user, Model $model): bool;

    abstract public function delete(User $user, Model $model): bool;

}