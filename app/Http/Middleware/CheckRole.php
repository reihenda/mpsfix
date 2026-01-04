<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Cek apakah role user ada di daftar role yang diizinkan
        foreach ($roles as $role) {
            switch ($role) {
                case 'admin':
                    if ($user->isAdmin()) return $next($request);
                    break;
                case 'superadmin':
                    if ($user->isSuperAdmin()) return $next($request);
                    break;
                case 'keuangan':
                    if ($user->isKeuangan()) return $next($request);
                    break;
                case 'customer':
                    if ($user->isCustomer()) return $next($request);
                    break;
                case 'fob':
                    if ($user->isFOB()) return $next($request);
                    break;
                case 'demo':
                    if ($user->isDemo()) return $next($request);
                    break;
            }
        }

        // Jika tidak punya akses, kembalikan error 403
        abort(403, 'Unauthorized access');
    }
}
