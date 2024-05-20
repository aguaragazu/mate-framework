<?php

namespace Mate\Providers;

use Mate\Crypto\Bcrypt;
use Mate\Crypto\Hasher;

class HasherServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("hashing.hasher", "bcrypt")) {
            "bcrypt" => singleton(Hasher::class, Bcrypt::class),
        };
    }
}
