<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Enforce role-based access control.
     * Usage in routes: middleware('role:HOD') or middleware('role:HOD,LECTURER')
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user || ! in_array($user->role, $roles)) {
            return response()->json(['message' => 'Forbidden. Insufficient role.'], 403);
        }

        return $next($request);
    }
}
