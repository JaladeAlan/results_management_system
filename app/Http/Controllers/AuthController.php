<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Authenticate users (HOD, Lecturer, RO) and return a JWT token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'role'     => 'required|in:HOD,LECTURER,RO',
        ]);

        $user = User::where('email', $request->email)
                    ->where('role', $request->role)
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not create token.'], 500);
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Invalidate the current JWT token.
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to logout.'], 500);
        }
    }

    /**
     * POST /api/auth/refresh
     * Refresh an expiring JWT token.
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $newToken]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token cannot be refreshed.'], 401);
        }
    }

    /**
     * GET /api/auth/me
     * Return the currently authenticated user's profile.
     */
    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]);
    }
}
