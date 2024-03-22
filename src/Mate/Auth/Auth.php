<?php

namespace Mate\Auth;

use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use Mate\Auth\Authenticatable;
use Mate\Auth\Authenticators\Authenticator;
use Mate\Routing\Route;

/**
 * Authentication facade.
 */
class Auth {
    /**
     * Authentication routes.
     */
    public static function routes() {
        Route::get('/login', [LoginController::class, 'show']);
        Route::post('/login', [LoginController::class, 'create']);
        Route::get('/logout', [LoginController::class, 'destroy']);
        Route::get('/register', [RegisterController::class, 'show']);
        Route::post('/register', [RegisterController::class, 'create']);
    }

    /**
     * Current logged in user.
     */
    public static function user(): ?Authenticatable {
        return app(Authenticator::class)->resolve();
    }

    /**
     * Check if current request is performed by guest.
     *
     * @return bool
     */
    public static function isGuest(): bool {
        return is_null(self::user());
    }
}
