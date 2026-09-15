<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use RespondsWithJson;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data + ['status' => 'active']);
        $user->sendEmailVerificationNotification();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('qismat')->plainTextToken,
            'email_verification_required' => true,
        ], 'Registration successful.', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', $data['login'])->orWhere('phone', $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Invalid credentials.']]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages(['login' => ['This account is not active.']]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('qismat')->plainTextToken,
        ], 'Login successful.');
    }

    public function me(Request $request)
    {
        return $this->success($request->user()->load('profile'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }
}
