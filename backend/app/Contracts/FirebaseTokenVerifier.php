<?php

namespace App\Contracts;

interface FirebaseTokenVerifier
{
    /** @return array<string, mixed> */
    public function verify(string $idToken): array;
}
