<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireToken
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->guard('api')->check()) {
            return $next($request);
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Invalid or missing access token'
        ], 401);
    }
}
