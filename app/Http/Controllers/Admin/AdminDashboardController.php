<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\DetailPenjualanStokAlokasi;
use App\Models\Pembelian; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 

class AdminDashboardController extends Controller
{
    public function index()
    {
        // --- Ringkasan Penjualan Hari Ini ---
        $today = Carbon::today();
        $penjualanHariIni = Penjualan::whereDate('tanggal_penjualan', $today)
                                     ->where('status_penjualan', 'SELESAI'); // Hanya yang selesai

        $jumlahTransaksiHariIni = $penjualanHariIni->count();
        $totalOmzetHariIni = (clone $penjualanHariIni)->sum('total_harga'); // Clone agar count tidak terpengaruh

        // --- Grafik Penjualan 7 Hari Terakhir (Sederhana - Data untuk Chart.js) ---
        $penjualanMingguanLabels = [];
        $penjualanMingguanData = [];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);
            $penjualanMingguanLabels[] = $tanggal->isoFormat('ddd, D MMM'); // Format: Sen, 1 Jan
            $totalHarian = Penjualan::whereDate('tanggal_penjualan', $tanggal)
                                    ->where('status_penjualan', 'SELESAI')
                                    ->sum('total_harga');
            $penjualanMingguanData[] = $totalHarian ?: 0;
        }

        // --- Ringkasan Stok Kritis ---
        $produkStokKritis = collect(); // Inisialisasi collection kosong
        $jumlahProdukStokKritis = 0;

        // Ambil semua produk aktif yang punya setting stok_minimum > 0 atau yang stoknya sudah 0
        $semuaProdukAktif = Produk::where('status', true)
            ->withCount([
                'stokBarang as total_stok_fisik' => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(jumlah), 0)'))
                          ->where('jumlah', '>', 0);
                }
            ])
            ->get();

        $itemsStokKritisUntukTampil = [];
        foreach ($semuaProdukAktif as $produk) {
            $totalDipesan = DetailPenjualanStokAlokasi::where('tipe_alokasi', 'DIALOKASIKAN_PESANAN')
                ->whereHas('detailPenjualan', function($qDetail) use ($produk) {
                    $qDetail->where('id_produk', $produk->id)
                            ->whereHas('penjualan', function($qPenjualan){
                                $qPenjualan->whereIn('status_penjualan', ['MENUNGGU_BARANG', 'MENUNGGU_PELUNASAN', 'SIAP_DIAMBIL']);
                            });
                })
                ->sum('jumlah_diambil');

            $stokFisik = $produk->total_stok_fisik ?: 0;
            $stokEfektif = $stokFisik - ($totalDipesan ?: 0);
            $stokMinimum = $produk->stok_minimum ?: 0;

            if (($stokMinimum > 0 && $stokEfektif <= $stokMinimum) || $stokEfektif <= 0) {
                $jumlahProdukStokKritis++;
                if (count($itemsStokKritisUntukTampil) < 5) { // Tampilkan maks 5 item
                    $itemsStokKritisUntukTampil[] = (object)[
                        'nama' => $produk->nama,
                        'kode_produk' => $produk->kode_produk,
                        'stok_efektif' => $stokEfektif,
                        'satuan' => $produk->satuan,
                        'stok_minimum' => $stokMinimum,
                        'id' => $produk->id // Untuk link ke detail batch
                    ];
                }
            }
        }
        $produkStokKritis = collect($itemsStokKritisUntukTampil);


        // --- Ringkasan Pembelian Aktif (Contoh) ---
        $jumlahPoAktif = Pembelian::whereIn('status_pembelian', ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN'])
                                  ->count();

        return view('admin.dashboard', compact(
            'jumlahTransaksiHariIni',
            'totalOmzetHariIni',
            'penjualanMingguanLabels',
            'penjualanMingguanData',
            'jumlahProdukStokKritis',
            'produkStokKritis', // Ini adalah collection dari objek
            'jumlahPoAktif'
        ));
    }
}