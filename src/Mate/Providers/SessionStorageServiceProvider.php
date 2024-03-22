<?php

namespace Mate\Providers;

use Mate\Session\Storage\NativeStorage;
use Mate\Session\Storage\SessionStorage;

class SessionStorageServiceProvider {
    public function registerServices() {
        match (config("session.storage", "native")) {
            "native" => singleton(SessionStorage::class, NativeStorage::class),
        };
    }
}
