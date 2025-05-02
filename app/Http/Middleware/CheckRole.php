<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth Facade

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string ...$roles // Terima satu atau lebih role sebagai argumen
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles) // Gunakan ...$roles
    {
        // Pastikan pengguna sudah login
        if (!Auth::check()) {
            return redirect('login');
        }

        // Ambil pengguna yang sedang login
        $user = Auth::user();

        // Periksa apakah peran pengguna ada dalam daftar $roles yang diizinkan
        foreach ($roles as $role) {
            // Gunakan model Pengguna Anda jika berbeda dari User default
            if ($user->role == $role) {
                return $next($request); // Izinkan akses jika peran cocok
            }
        }

        // Jika tidak ada peran yang cocok, kembalikan error atau redirect
        // abort(403, 'Akses Ditolak: Anda tidak memiliki peran yang sesuai.');
        // Atau redirect ke halaman sebelumnya dengan pesan error
         return redirect()->back()->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        // Atau redirect ke halaman dashboard dengan pesan error
        // return redirect('/home')->with('error', 'Akses ditolak.');

    }
}