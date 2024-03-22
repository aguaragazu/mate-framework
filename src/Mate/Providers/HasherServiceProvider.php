<?php

namespace Mate\Providers;

use Mate\Crypto\Bcrypt;
use Mate\Crypto\Hasher;

class HasherServiceProvider {
    public function registerServices() {
        match (config("hashing.hasher", "bcrypt")) {
            "bcrypt" => singleton(Hasher::class, Bcrypt::class),
        };
    }
}
