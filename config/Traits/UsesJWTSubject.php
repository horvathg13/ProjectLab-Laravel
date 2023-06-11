<?php

namespace App\Traits;

use Tymon\JWTAuth\Contracts\JWTSubject;

trait UsesJWTSubject
{
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}