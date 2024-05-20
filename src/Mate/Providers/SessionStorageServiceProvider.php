<?php

namespace Mate\Providers;

use Mate\Session\PhpNativeSessionStorage;
use Mate\Session\SessionStorage;

class SessionStorageServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("session.storage", "native")) {
            "native" => singleton(SessionStorage::class, PhpNativeSessionStorage::class),
        };
    }
}
