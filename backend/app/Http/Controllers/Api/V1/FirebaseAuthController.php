<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FirebaseAuthController extends Controller
{
    use RespondsWithJson;

    public function exchange(Request $request)
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('firebase_claims', []);
        $uid = (string) ($claims['sub'] ?? '');
        $email = filter_var($claims['email'] ?? null, FILTER_VALIDATE_EMAIL);

        abort_if($uid === '' || $email === false, 422, 'Firebase account must contain a valid email address.');
        abort_unless(($claims['email_verified'] ?? false) === true, 403, 'Firebase email address is not verified.');

        $user = DB::transaction(function () use ($claims, $uid, $email) {
            $identityUser = User::where('firebase_uid', $uid)->lockForUpdate()->first();
            $emailUser = User::where('email', $email)->lockForUpdate()->first();

            if ($identityUser && $emailUser && $identityUser->isNot($emailUser)) {
                throw new ConflictHttpException('Email address is already linked to another Qismat account.');
            }

            $user = $identityUser ?? $emailUser;

            if ($user?->firebase_uid && $user->firebase_uid !== $uid) {
                throw new ConflictHttpException('Email address is already linked to another Firebase account.');
            }

            $user ??= new User;
            $displayName = trim((string) ($claims['name'] ?? '')) ?: Str::before($email, '@');
            $user->firebase_uid = $uid;
            $user->email = $email;
            $user->name = Str::limit($displayName, 120, '');
            $user->email_verified_at = now();
            $user->status ??= 'active';

            if (! $user->exists) {
                $user->password = Str::random(64);
            }

            $user->last_login_at = now();
            $user->save();

            return $user;
        });

        abort_unless($user->status === 'active', 403, 'This Qismat account is not active.');

        return $this->success([
            'user' => $user->load('profile'),
            'token' => $user->createToken('qismat')->plainTextToken,
        ], 'Firebase authentication successful.');
    }
}
