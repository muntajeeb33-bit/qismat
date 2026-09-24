<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FirebaseAuthController extends Controller
{
    use RespondsWithJson;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $credential = $this->firebaseRequest('signUp', [
            'email' => $data['email'],
            'password' => $data['password'],
            'returnSecureToken' => true,
        ]);
        $updated = $this->firebaseRequest('update', [
            'idToken' => $credential['idToken'],
            'displayName' => $data['name'],
            'returnSecureToken' => true,
        ]);
        $this->firebaseRequest('sendOobCode', [
            'requestType' => 'VERIFY_EMAIL',
            'idToken' => $updated['idToken'] ?? $credential['idToken'],
        ]);

        return $this->success(null, 'Account created. Check your email to verify your account.', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credential = $this->firebaseRequest('signInWithPassword', [
            'email' => $data['email'],
            'password' => $data['password'],
            'returnSecureToken' => true,
        ]);
        $account = $this->firebaseRequest('lookup', ['idToken' => $credential['idToken']]);

        if (! ($account['users'][0]['emailVerified'] ?? false)) {
            $this->firebaseRequest('sendOobCode', [
                'requestType' => 'VERIFY_EMAIL',
                'idToken' => $credential['idToken'],
            ]);
            abort(403, 'Verify your email before signing in. We sent a new verification link.');
        }

        return $this->success(['id_token' => $credential['idToken']], 'Firebase sign-in successful.');
    }

    public function passwordReset(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $this->firebaseRequest('sendOobCode', [
            'requestType' => 'PASSWORD_RESET',
            'email' => $data['email'],
        ]);

        return $this->success(null, 'Password reset email sent.');
    }

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

    /** @return array<string, mixed> */
    private function firebaseRequest(string $action, array $payload): array
    {
        $apiKey = trim((string) config('firebase.web_api_key'));
        abort_if($apiKey === '', 503, 'Firebase authentication is not configured.');

        $response = Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Origin' => (string) config('app.frontend_url', 'https://qismatconnections.com'),
                'Referer' => rtrim((string) config('app.frontend_url', 'https://qismatconnections.com'), '/').'/',
            ])
            ->timeout(15)
            ->post("https://identitytoolkit.googleapis.com/v1/accounts:{$action}?key=".urlencode($apiKey), $payload);

        if ($response->failed()) {
            $code = Str::before((string) $response->json('error.message', 'FIREBASE_REQUEST_FAILED'), ' : ');
            $messages = [
                'EMAIL_EXISTS' => ['An account already exists for this email.', 409],
                'OPERATION_NOT_ALLOWED' => ['Email and password registration is not enabled.', 503],
                'USER_DISABLED' => ['This account has been disabled.', 403],
                'EMAIL_NOT_FOUND' => ['The email or password is incorrect.', 401],
                'INVALID_PASSWORD' => ['The email or password is incorrect.', 401],
                'INVALID_LOGIN_CREDENTIALS' => ['The email or password is incorrect.', 401],
                'INVALID_EMAIL' => ['Enter a valid email address.', 422],
                'WEAK_PASSWORD' => ['Choose a stronger password with at least eight characters.', 422],
                'TOO_MANY_ATTEMPTS_TRY_LATER' => ['Too many attempts. Please wait and try again.', 429],
                'API_KEY_NOT_VALID' => ['Firebase configuration is invalid. Please contact support.', 503],
            ];
            [$message, $status] = $messages[$code] ?? ['Authentication could not be completed.', 502];
            abort($status, $message);
        }

        return $response->json();
    }
}
