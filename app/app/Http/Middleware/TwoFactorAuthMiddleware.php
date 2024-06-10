<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class TwoFactorAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (
            $user
            && !$request->session()->get('two_factor_passed')
            && $request->session()->has('is_password_login'))
        {
            return Redirect::route('two-factor-authentication');
        }

        return $next($request);
    }
}
