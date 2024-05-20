<?php

namespace Mate\Providers;

use Mate\View\MateEngine;
use Mate\View\View;

class ViewServiceProvider implements ServiceProvider
{
    public function registerServices()
    {
        match (config("view.engine", "mate")) {
            "mate" => singleton(View::class, fn () => new MateEngine(config("view.path"))),
        };
    }
}
