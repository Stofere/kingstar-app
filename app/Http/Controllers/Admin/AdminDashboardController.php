<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna; // Pastikan model Pengguna sudah ada
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Misalnya, menghitung jumlah pengguna di dashboard Admin
        $totalUsers = Pengguna::count();

        // Mengirimkan data ke view
        return view('admin.dashboard', compact('totalUsers'));
    }
}
