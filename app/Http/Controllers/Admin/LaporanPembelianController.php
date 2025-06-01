<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\Supplier; 
use App\Models\Pengguna;  // Untuk filter pengguna (jika perlu, misal siapa yg buat PO)
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LaporanPembelianController extends Controller
{
    /**
     * Menampilkan halaman utama laporan pembelian.
     */
    public function index(Request $request)
    {
        $supplierOptions = Supplier::where('status', true)->orderBy('nama')->pluck('nama', 'id');
        // Status pembelian bisa diambil dari konstanta atau didefinisikan di sini
        $statusPembelianOptions = [
            'DRAFT' => 'Draft',
            'DIPESAN' => 'Dipesan',
            'PENGIRIMAN' => 'Pengiriman',
            'TIBA_SEBAGIAN' => 'Tiba Sebagian',
            'SELESAI' => 'Selesai',
            'DIBATALKAN' => 'Dibatalkan',
        ];
        $statusPembayaranOptions = [
            'BELUM_LUNAS' => 'Belum Lunas',
            'LUNAS' => 'Lunas',
            'JATUH_TEMPO' => 'Jatuh Tempo', // Jika ada
        ];

        // Jika request adalah AJAX dari DataTables, panggil method data
        if ($request->ajax()) {
            return $this->getPembelianData($request);
        }

        return view('admin.laporan.pembelian.index', compact(
            'supplierOptions',
            'statusPembelianOptions',
            'statusPembayaranOptions'
        ));
    }

    /**
     * Mengambil data pembelian untuk DataTables server-side.
     */
    public function getPembelianData(Request $request)
    {
        $query = Pembelian::with(['supplier', 'pengguna']) // Eager load relasi
                          ->select('pembelian.*');

        // Filter Rentang Tanggal Pembelian
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalAkhir = $request->input('tanggal_akhir');

        if ($tanggalMulai && $tanggalAkhir) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $tanggalMulai)->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $tanggalAkhir)->endOfDay();
                $query->whereBetween('pembelian.tanggal_pembelian', [$startDate, $endDate]);
            } catch (\Exception $e) {
                // Log error jika perlu
            }
        } else {
            // Default filter jika tidak ada input tanggal (misalnya, 1 bulan terakhir)
            $query->where('pembelian.tanggal_pembelian', '>=', Carbon::now()->subMonth()->startOfDay());
            $query->where('pembelian.tanggal_pembelian', '<=', Carbon::now()->endOfDay());
        }

        // Filter Tambahan
        if ($request->filled('id_supplier')) {
            $query->where('pembelian.id_supplier', $request->input('id_supplier'));
        }
        if ($request->filled('status_pembelian')) {
            $query->where('pembelian.status_pembelian', $request->input('status_pembelian'));
        }
        if ($request->filled('status_pembayaran')) {
            $query->where('pembelian.status_pembayaran', $request->input('status_pembayaran'));
        }
        // Anda bisa tambahkan filter berdasarkan 'id_pengguna' jika perlu

        $dataTable = DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('supplier_nama', function (Pembelian $pembelian) {
                return $pembelian->supplier->nama ?? '-';
            })
            ->addColumn('pengguna_nama', function (Pembelian $pembelian) {
                return $pembelian->pengguna->nama ?? '-'; // Pengguna yang membuat PO
            })
            ->addColumn('tanggal_pembelian_formatted', function (Pembelian $pembelian) {
                return Carbon::parse($pembelian->tanggal_pembelian)->isoFormat('D MMM YYYY');
            })
            ->addColumn('total_harga_formatted', function (Pembelian $pembelian) {
                return 'Rp ' . number_format($pembelian->total_harga, 0, ',', '.');
            })
            ->addColumn('status_pembelian_badge', function (Pembelian $pembelian) {
                $badgeClass = 'bg-secondary';
                switch ($pembelian->status_pembelian) {
                    case 'DIPESAN': $badgeClass = 'bg-info'; break;
                    case 'PENGIRIMAN': $badgeClass = 'bg-primary'; break;
                    case 'TIBA_SEBAGIAN': $badgeClass = 'bg-warning text-dark'; break;
                    case 'SELESAI': $badgeClass = 'bg-success'; break;
                    case 'DIBATALKAN': $badgeClass = 'bg-danger'; break;
                    case 'DRAFT': $badgeClass = 'bg-light text-dark border'; break;
                }
                return '<span class="badge ' . $badgeClass . '">' . str_replace('_', ' ', $pembelian->status_pembelian) . '</span>';
            })
            ->addColumn('status_pembayaran_badge', function (Pembelian $pembelian) {
                $badgeClass = 'bg-secondary';
                if ($pembelian->status_pembayaran == 'LUNAS') $badgeClass = 'bg-success';
                else if ($pembelian->status_pembayaran == 'BELUM_LUNAS') $badgeClass = 'bg-danger';
                else if ($pembelian->status_pembayaran == 'JATUH_TEMPO') $badgeClass = 'bg-warning text-dark';
                return '<span class="badge ' . $badgeClass . '">' . str_replace('_', ' ', $pembelian->status_pembayaran) . '</span>';
            })
            ->addColumn('action', function (Pembelian $pembelian) {
                $detailUrl = route('admin.pembelian.show', $pembelian->id); // Asumsi ada route detail PO
                // Tombol untuk "Terima Barang" bisa ditambahkan jika statusnya relevan
                $terimaBarangUrl = '';
                if (in_array($pembelian->status_pembelian, ['DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN'])) {
                    $terimaBarangUrl = route('gudang.penerimaan.create', ['pembelian' => $pembelian->id]);
                }

                $btn = '<a href="'.$detailUrl.'" class="btn btn-info btn-sm me-1" title="Detail Pembelian"><i class="bi bi-eye"></i></a>';
                if ($terimaBarangUrl) {
                    $btn .= '<a href="'.$terimaBarangUrl.'" class="btn btn-success btn-sm" title="Proses Penerimaan Barang"><i class="bi bi-box-arrow-in-down"></i></a>';
                }
                return $btn;
            })
            ->rawColumns(['status_pembelian_badge', 'status_pembayaran_badge', 'action']);

        // Hitung total pembelian berdasarkan query yang sudah difilter
        $queryForTotal = clone $dataTable->getQuery();
        $totalKeseluruhan = $queryForTotal->sum('pembelian.total_harga');

        return $dataTable->with('total_keseluruhan_pembelian', $totalKeseluruhan)
                         ->make(true);
    }
}