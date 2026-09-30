<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengakhiri sesi akun yang dinonaktifkan atau dicabut perannya oleh admin, tanpa menunggu pengguna keluar sendiri.
 */
class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->is_active || $user->getRoleNames()->isEmpty())) {
            $message = $user->is_active
                ? 'Akun Anda belum memiliki peran. Hubungi admin untuk mengaktifkannya.'
                : 'Akun Anda nonaktif.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
