<?php

namespace App\Http\Middleware;

use App\Contracts\FirebaseTokenVerifier;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyFirebaseToken
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idToken = $request->bearerToken();

        if (! $idToken) {
            return $this->unauthorized('Firebase ID token is required.');
        }

        try {
            $claims = $this->container->make(FirebaseTokenVerifier::class)->verify($idToken);
        } catch (Throwable) {
            return $this->unauthorized('Firebase ID token is invalid or expired.');
        }

        $request->attributes->set('firebase_claims', $claims);

        return $next($request);
    }

    private function unauthorized(string $message)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) [],
        ], 401);
    }
}
