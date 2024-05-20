<?php

namespace Mate\Providers;

use Mate\App;
use Mate\Storage\Drivers\DiskFileStorage;
use Mate\Storage\Drivers\FileStorageDriver;

class FileStorageDriverServiceProvider
{
    public function registerServices()
    {
        match (config("storage.driver", "disk")) {
            "disk" => singleton(
                FileStorageDriver::class,
                fn () => new DiskFileStorage(
                    App::$root . "/storage",
                    "storage",
                    config("app.url")
                )
            ),
        };
    }
}
