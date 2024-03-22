<?php

use Mate\Auth\Auth;
use Mate\Auth\Authenticatable;

/**
 * Resolve authenticatable instance.
 *
 * @return Authenticatable
 */
function auth(): ?Authenticatable {
    return Auth::user();
}

/**
 * Check if the request was performed by unauthenticated user.
 *
 * @return bool
 */
function isGuest(): bool {
    return Auth::isGuest();
}
