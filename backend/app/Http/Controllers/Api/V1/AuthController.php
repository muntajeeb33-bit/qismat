<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['nullable','email','unique:users,email','required_without:phone'],
            'phone' => ['nullable','string','max:30','unique:users,phone','required_without:email'],
            'password' => ['required','string','min:8','confirmed'],
        ]);
        $user = User::create($data + ['status' => 'active']);
        return response()->json(['user'=>$user,'token'=>$user->createToken('qismat')->plainTextToken], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['login'=>['required','string'],'password'=>['required','string']]);
        $user = User::where('email',$data['login'])->orWhere('phone',$data['login'])->first();
        if (!$user || !Hash::check($data['password'],$user->password)) throw ValidationException::withMessages(['login'=>['Invalid credentials.']]);
        $user->forceFill(['last_login_at'=>now()])->save();
        return ['user'=>$user,'token'=>$user->createToken('qismat')->plainTextToken];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok'=>true]);
    }
}
