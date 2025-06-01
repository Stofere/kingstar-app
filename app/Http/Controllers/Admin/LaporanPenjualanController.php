<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\Pelanggan; // Untuk filter pelanggan
use App\Models\Pengguna;  // Untuk filter kasir
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables; 
use Carbon\Carbon;

class LaporanPenjualanController extends Controller
{
    /**
     * Menampilkan halaman utama laporan penjualan.
     */
    public function index(Request $request)
    {
        // Ambil data untuk filter dropdown jika diperlukan
        $pelangganOptions = Pelanggan::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        $kasirOptions = Pengguna::whereIn('role', ['KASIR', 'ADMIN'])->where('status',true)->orderBy('nama')->pluck('nama', 'id');
        $tipeTransaksiOptions = ['BIASA' => 'Biasa', 'PESAN_BARANG' => 'Pesan Barang'];
        $statusPenjualanOptions = [
            'PROSES' => 'Proses', 'MENUNGGU_BARANG' => 'Menunggu Barang',
            'MENUNGGU_PELUNASAN' => 'Menunggu Pelunasan', 'SIAP_DIAMBIL' => 'Siap Diambil',
            'PENGIRIMAN' => 'Pengiriman', 'SELESAI' => 'Selesai',
            'DIBATALKAN' => 'Dibatalkan', 'STOK_TIDAK_CUKUP' => 'Stok Tidak Cukup'
        ];
        $kanalTransaksiOptions = ['TOKO' => 'Toko Fisik', 'TOKOPEDIA' => 'Tokopedia', 'SHOPEE' => 'Shopee'];


        // Jika request adalah AJAX dari DataTables, panggil method data
        if ($request->ajax()) {
            return $this->getPenjualanData($request);
        }

        return view('admin.laporan.penjualan.index', compact(
            'pelangganOptions',
            'kasirOptions',
            'tipeTransaksiOptions',
            'statusPenjualanOptions',
            'kanalTransaksiOptions'
        ));
    }

    /**
     * Mengambil data penjualan untuk DataTables server-side.
     */
    public function getPenjualanData(Request $request)
    {
        $query = Penjualan::with(['pelanggan', 'pengguna']) // Eager load relasi
                         ->select('penjualan.*'); // Pilih semua kolom dari tabel penjualan

        // Filter Rentang Tanggal (WAJIB ADA, minimal default 1 bulan terakhir atau hari ini)
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalAkhir = $request->input('tanggal_akhir');

        if ($tanggalMulai && $tanggalAkhir) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $tanggalMulai)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $tanggalAkhir)->endOfDay();
                $query->whereBetween('penjualan.tanggal_penjualan', [$startDate, $endDate]);
            } catch (\Exception $e) {
                // Handle error parsing tanggal jika perlu, atau biarkan default
            }
        } else {
            // Default filter jika tidak ada input tanggal (misalnya, 1 bulan terakhir)
            $query->where('penjualan.tanggal_penjualan', '>=', Carbon::now()->subMonth()->startOfDay());
            $query->where('penjualan.tanggal_penjualan', '<=', Carbon::now()->endOfDay());
        }

        // Filter Tambahan
        if ($request->filled('id_pelanggan')) {
            $query->where('penjualan.id_pelanggan', $request->input('id_pelanggan'));
        }
        if ($request->filled('id_kasir')) {
            $query->where('penjualan.id_pengguna', $request->input('id_kasir'));
        }
        if ($request->filled('tipe_transaksi')) {
            $query->where('penjualan.tipe_transaksi', $request->input('tipe_transaksi'));
        }
        if ($request->filled('status_penjualan')) {
            $query->where('penjualan.status_penjualan', $request->input('status_penjualan'));
        }
        if ($request->filled('kanal_transaksi')) {
            $query->where('penjualan.kanal_transaksi', $request->input('kanal_transaksi'));
        }

        return DataTables::eloquent($query)
            ->addIndexColumn() // Menambahkan kolom DT_RowIndex (nomor urut)
            ->addColumn('pelanggan_nama', function (Penjualan $penjualan) {
                return $penjualan->pelanggan->nama ?? 'Umum';
            })
            ->addColumn('kasir_nama', function (Penjualan $penjualan) {
                return $penjualan->pengguna->nama ?? '-';
            })
            ->addColumn('tanggal_penjualan_formatted', function (Penjualan $penjualan) {
                return Carbon::parse($penjualan->tanggal_penjualan)->isoFormat('D MMM YYYY, HH:mm');
            })
            ->addColumn('total_harga_formatted', function (Penjualan $penjualan) {
                return 'Rp ' . number_format($penjualan->total_harga, 0, ',', '.');
            })
            ->addColumn('status_penjualan_badge', function (Penjualan $penjualan) {
                // Logika untuk badge status, contoh:
                $badgeClass = 'bg-secondary';
                if ($penjualan->status_penjualan == 'SELESAI') $badgeClass = 'bg-success';
                else if ($penjualan->status_penjualan == 'MENUNGGU_PELUNASAN') $badgeClass = 'bg-warning text-dark';
                else if ($penjualan->status_penjualan == 'DIBATALKAN') $badgeClass = 'bg-danger';
                else if ($penjualan->status_penjualan == 'STOK_TIDAK_CUKUP') $badgeClass = 'bg-danger';
                else if ($penjualan->status_penjualan == 'MENUNGGU_BARANG') $badgeClass = 'bg-info';
                else if ($penjualan->status_penjualan == 'SIAP_DIAMBIL') $badgeClass = 'bg-primary';
                return '<span class="badge ' . $badgeClass . '">' . str_replace('_', ' ', $penjualan->status_penjualan) . '</span>';
            })
            ->addColumn('status_pembayaran_badge', function (Penjualan $penjualan) {
                $badgeClass = 'bg-secondary';
                if ($penjualan->status_pembayaran == 'LUNAS') $badgeClass = 'bg-success';
                else if ($penjualan->status_pembayaran == 'DP') $badgeClass = 'bg-info';
                else if ($penjualan->status_pembayaran == 'BELUM_LUNAS') $badgeClass = 'bg-danger';
                return '<span class="badge ' . $badgeClass . '">' . str_replace('_', ' ', $penjualan->status_pembayaran) . '</span>';
            })
            ->addColumn('action', function (Penjualan $penjualan) {
                // Tombol aksi (misal: lihat detail, cetak nota ulang)
                // $detailUrl = route('admin.penjualan.show', $penjualan->id); // Jika ada route detail
                $notaUrl = route('kasir.penjualan.nota', $penjualan->id); // Gunakan route nota yang sudah ada
                $btn = '<a href="'.$notaUrl.'" class="btn btn-info btn-sm me-1" title="Lihat Nota" target="_blank"><i class="bi bi-receipt"></i></a>';
                // $btn .= '<a href="'.$detailUrl.'" class="btn btn-primary btn-sm" title="Detail Transaksi"><i class="bi bi-eye"></i></a>';
                return $btn;
            })
            ->rawColumns(['status_penjualan_badge', 'status_pembayaran_badge', 'action']) // Kolom yang mengandung HTML
            ->make(true); // Kirim response
    }
}