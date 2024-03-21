<?php

namespace App\Http\Middleware;

use App\Http\Helpers\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckForBlockedUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->user()->is_blocked)
        {
            // return ApiResponse::send('You\'re blocked', 0, 423, 'You\'re blocked');
        }

        return $next($request);
    }
}
