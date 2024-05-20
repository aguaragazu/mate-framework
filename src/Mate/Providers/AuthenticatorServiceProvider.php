<?php

namespace Mate\Providers;

use Mate\Auth\Authenticators\Authenticator;
use Mate\Auth\Authenticators\JwtAuthenticator;
use Mate\Auth\Authenticators\SessionAuthenticator;

class AuthenticatorServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        $resourceType = $this->getRequestType();

        $autConfig = config("auth.{$resourceType}.method", 'session');

        match ($autConfig) {
            "session" => singleton(Authenticator::class, SessionAuthenticator::class),
            "token" => singleton(Authenticator::class, JwtAuthenticator::class),
            default => throw new \InvalidArgumentException("Invalid authentication method: {$autConfig}")
        };
    }

    protected function getRequestType(): string {
        
        $path = request()->getPath();

        if (str_starts_with($path, '/api')) {
            return 'api';
        }

        if (str_starts_with($path, '/admin')) {
            return 'admin';
        }

        return 'web';
    }
}
