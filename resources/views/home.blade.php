<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Import Auth

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth'); // Pastikan hanya user terautentikasi
    }

    /**
     * Show the application dashboard.
     * Redirect based on user role.
     *
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role == 'ADMIN') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role == 'KASIR') {
            return redirect()->route('kasir.dashboard');
        } elseif ($user->role == 'GUDANG') {
            return redirect()->route('gudang.dashboard');
        } else {
            // Fallback jika role tidak dikenal (seharusnya tidak terjadi)
            // Atau bisa logout user
            Auth::logout();
            return redirect('/login')->with('error', 'Role pengguna tidak valid.');
        }
        // Baris view('home') bawaan tidak akan tercapai jika redirect berhasil
        // return view('home');
    }
}