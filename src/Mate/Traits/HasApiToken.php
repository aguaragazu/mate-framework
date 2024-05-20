<?php
namespace Mate\Traits;

use Mate\Auth\Authenticators\Authenticator;

trait HasApiToken
{
    public function getToken() {
        return app(Authenticator::class)->login($this);
    }
}