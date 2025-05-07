<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GudangDashboardController extends Controller
{
    public function index()
    {
        return view('gudang.dashboard'); // Halaman dashboard gudang
    }
}
