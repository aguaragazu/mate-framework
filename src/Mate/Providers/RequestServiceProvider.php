<?php

namespace Mate\Providers;

use Mate\Http\Request;

class RequestServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        singleton(Request::class, function() {
            return new Request();
        });
    }
}