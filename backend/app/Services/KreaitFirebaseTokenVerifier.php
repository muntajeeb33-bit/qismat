<?php

namespace App\Services;

use App\Contracts\FirebaseTokenVerifier;
use Kreait\Firebase\Contract\Auth;

class KreaitFirebaseTokenVerifier implements FirebaseTokenVerifier
{
    public function __construct(private readonly Auth $auth) {}

    public function verify(string $idToken): array
    {
        return $this->auth->verifyIdToken($idToken, true)->claims()->all();
    }
}
