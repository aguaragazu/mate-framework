<?php

namespace Mate\Auth;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Mate\Auth\Authenticators\Authenticator;
use Mate\Routing\Route;

class Auth
{
    public static function user(): ?Authenticatable
    {
        return app(Authenticator::class)->resolve();
    }

    public static function isGuest(): bool
    {
        return is_null(self::user());
    }

    public static function routes()
    {
        Route::get('/register', [RegisterController::class, 'create']);
        Route::post('/register', [RegisterController::class, 'store']);
        Route::get('/login', [LoginController::class, 'create']);
        Route::post('/login', [LoginController::class, 'store']);
        Route::get('/logout', [LoginController::class, 'destroy']);
    }
}
