<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan';

    protected $fillable = [
        'id_penjualan',
        'id_produk',
        'nama_produk_snapshot',
        'kode_produk_snapshot',
        'jumlah',
        'harga_jual',
        'subtotal',
        'nomor_seri_terjual',
        'status_bayar_konsinyasi',
        'customer_garansi_mulai_at',
        'customer_garansi_berakhir_at',
        'catatan',
    ];

    // Relasi yang sudah ada
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function stokAlokasi()
    {
        return $this->hasMany(DetailPenjualanStokAlokasi::class, 'id_detail_penjualan');
    }


    /**
     * Mendapatkan semua record retur penjualan yang terkait dengan item detail penjualan ini.
     * Satu item detail penjualan bisa diretur beberapa kali (misal, retur parsial).
     */
    public function returPenjualan() // Nama method harus konsisten dengan yang Anda panggil
    {
        return $this->hasMany(ReturPenjualan::class, 'id_detail_penjualan');
    }


    // Accessor untuk menghitung total jumlah yang sudah diretur (opsional, tapi berguna)
    public function getTotalJumlahDireturAttribute()
    {

        if ($this->relationLoaded('returPenjualan')) { 
            return $this->returPenjualan->sum('jumlah_retur');
        }
        return $this->returPenjualan()->sum('jumlah_retur');
    }
}