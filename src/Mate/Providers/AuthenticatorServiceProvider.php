<?php

namespace Mate\Providers;

use Mate\Auth\Authenticators\Authenticator;
use Mate\Auth\Authenticators\SessionAuthenticator;

class AuthenticatorServiceProvider {
    public function registerServices() {
        match (config("auth.method", "session")) {
            "session" => singleton(Authenticator::class, SessionAuthenticator::class),
        };
    }
}
