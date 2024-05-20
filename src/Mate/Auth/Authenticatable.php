<?php

namespace Mate\Auth;

use Mate\Auth\Authenticators\Authenticator;
use Mate\Database\Model;
use Mate\Http\Request;

class Authenticatable extends Model
{
    public function id(): int|string
    {
        return $this->{$this->primaryKey} ?? '';
    }

    public function login()
    {
        app(Authenticator::class)->login($this);
    }

    public function logout()
    {
        app(Authenticator::class)->logout($this);
    }

    public function isAuthenticated()
    {
        app(Authenticator::class)->isAuthenticated($this);
    }

    public function user()
    {
        return app(Authenticator::class)->user($this);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id(),
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public function __toString()
    {
        return $this->name;
    }

    public function toArray(?Request $request = null): array
    {
        return $this->jsonSerialize();
    }
}
