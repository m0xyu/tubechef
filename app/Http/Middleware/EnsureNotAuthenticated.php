<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (Auth::check()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'すでにログイン中です。',
        //         'error_code' => 'ALREADY_AUTHENTICATED'
        //     ], 403);
        // }

        return $next($request);
    }
}
