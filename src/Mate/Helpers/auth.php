<?php

use Mate\Auth\Auth;
use Mate\Auth\Authenticatable;

function auth(): ?Authenticatable
{
    return Auth::user();
}

function isGuest(): bool
{
    return Auth::isGuest();
}
