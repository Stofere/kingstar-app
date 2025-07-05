<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DetailReturPenjualan
 *
 * Merepresentasikan setiap item/produk yang diretur dalam
 * satu transaksi retur (Nota Retur).
 */
class DetailReturPenjualan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'detail_retur_penjualan';

    /**
     * Kolom-kolom yang dapat diisi secara massal (mass assignable).
     * Ini penting untuk keamanan saat menggunakan metode Model::create().
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_retur_penjualan',
        'id_detail_penjualan_asal',
        'jumlah_retur',
        'nomor_seri_diretur',
        'alasan_retur',
        'tindakan_lanjut',
        'catatan_pelanggan',
        'id_dpsa_asal',
    ];

    // =================================================================================
    // RELASI-RELASI (Relationships)
    // =================================================================================

    /**
     * Mendefinisikan relasi "belongsTo" ke ReturPenjualan (Header/Nota Retur).
     * Setiap detail retur PASTI milik SATU nota retur.
     */
    public function returPenjualan()
    {
        return $this->belongsTo(ReturPenjualan::class, 'id_retur_penjualan');
    }

    /**
     * Mendefinisikan relasi "belongsTo" ke DetailPenjualan (Item Asal).
     * Setiap detail retur berasal dari SATU item spesifik di nota penjualan awal.
     */
    public function detailPenjualanAsal()
    {
        return $this->belongsTo(DetailPenjualan::class, 'id_detail_penjualan_asal');
    }

    public function alokasiAsal()
    {
        return $this->belongsTo(DetailPenjualanStokAlokasi::class, 'id_dpsa_asal');
    }
}