<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KasirDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $user = Auth::user();

        // 1. Total Penjualan Toko Keseluruhan Hari Ini
        $totalPenjualanTokoHariIni = Penjualan::whereDate('tanggal_penjualan', $today)
                                            ->where('status_penjualan', 'SELESAI') // Hanya yang sudah selesai
                                            ->sum('total_harga');
        $jumlahTransaksiTokoHariIni = Penjualan::whereDate('tanggal_penjualan', $today)
                                             ->where('status_penjualan', 'SELESAI')
                                             ->count();

        // 2. Daftar Transaksi Terakhir (misal 5 terakhir oleh SEMUA kasir, atau filter by user jika perlu)
        $transaksiTerakhir = Penjualan::with('pelanggan')
                                ->where('status_penjualan', 'SELESAI') // Atau bisa juga tampilkan yang masih PROSES
                                ->orderBy('tanggal_penjualan', 'desc')
                                ->limit(5)
                                ->get();

        // 3. Pesan Barang Menunggu Pelunasan/Pengambilan
        $pesanBarangMenunggu = Penjualan::where('tipe_transaksi', 'PESAN_BARANG')
                                        ->whereIn('status_penjualan', ['MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL'])
                                        ->with('pelanggan', 'detailPenjualan.produk') // Eager load untuk info
                                        ->orderBy('tanggal_penjualan', 'asc')
                                        ->limit(5) // Tampilkan beberapa saja di dashboard
                                        ->get();
        $jumlahPesanBarangMenunggu = Penjualan::where('tipe_transaksi', 'PESAN_BARANG')
                                        ->whereIn('status_penjualan', ['MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL'])
                                        ->count();


        return view('kasir.dashboard', compact(
            'totalPenjualanTokoHariIni',
            'jumlahTransaksiTokoHariIni',
            'transaksiTerakhir',
            'pesanBarangMenunggu',
            'jumlahPesanBarangMenunggu'
        ));
    }
}