<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu.');
        }

        $user = Auth::user();

        // Check if role is admin or email domain is @tandang.id
        if ($user->role === 'admin' || str_ends_with(strtolower($user->email), '@tandang.id')) {
            return $next($request);
        }

        // Non-admin user attempting to access admin route
        return redirect()->route('login')->with('error', 'Akses ditolak. Halaman tersebut khusus Administrator Tandang.');
    }
}

