<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message'   =>  'Invalid Credentials',
            ], 401);
        }

        $user = $request->user();

        if (! $user->is_active) {
            return response()->json([
                'message'   => 'Account is deactivated',
            ], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('role'),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('role')
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
