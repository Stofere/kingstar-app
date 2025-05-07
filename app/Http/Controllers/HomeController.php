<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     * Redirect based on user role.
     *
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request) 
    {
        $user = Auth::user();

        if ($user->role == 'ADMIN') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role == 'KASIR') {
            return redirect()->route('kasir.dashboard');
        } elseif ($user->role == 'GUDANG') {
            return redirect()->route('gudang.dashboard');
        } else {
            // Fallback jika role tidak dikenal
            Auth::logout(); // Logout user karena role tidak valid
            $request->session()->invalidate(); // Invalidate session
            $request->session()->regenerateToken(); // Regenerate token
            return redirect('/login')->with('error', 'Role pengguna tidak valid. Silakan hubungi Admin.');
        }
    }
}