<?php

namespace Mate\Auth;

use Mate\Auth\Authenticators\Authenticator;
use Mate\Database\Model;

/**
 * Authenticatable model.
 */
class Authenticatable extends Model {
    /**
     * Check if this instance is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool {
        return app(Authenticator::class)->isAuthenticated($this);
    }

    /**
     * Authenticatable ID.
     *
     * @return int
     */
    public function id(): int {
        return $this->{$this->primaryKey};
    }

    /**
     * Authenitcate.
     */
    public function login() {
        app(Authenticator::class)->login($this);
    }

    /**
     * Make this instance unauthenticated.
     */
    public function logout() {
        app(Authenticator::class)->logout($this);
    }
}
