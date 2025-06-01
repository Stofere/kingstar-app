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
        'jumlah',
        'harga_jual',
        'nama_produk_snapshot',
        'kode_produk_snapshot',
        'subtotal',
        'nomor_seri_terjual', 
        'status_bayar_konsinyasi',
        'customer_garansi_mulai_at',
        'customer_garansi_berakhir_at',
        'catatan',
    ];

    protected $casts = [
        'harga_jual' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'customer_garansi_mulai_at' => 'date',
        'customer_garansi_berakhir_at' => 'date',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan');
    }

    public function returPenjualan()
    {
        return $this->hasOne(ReturPenjualan::class, 'id_detail_penjualan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    /**
     * Mendapatkan SEMUA alokasi stok (baik pra-alokasi pesanan maupun pengeluaran biasa)
     * yang terkait dengan detail penjualan ini.
     */
    public function stokAlokasi() // Nama ini sudah umum dan bagus
    {
        return $this->hasMany(DetailPenjualanStokAlokasi::class, 'id_detail_penjualan');
    }


    // Anda bisa menambahkan accessor untuk mendapatkan nomor seri terjual sebagai array jika perlu
    public function getNomorSeriTerjualArrayAttribute()
    {
        if (!empty($this->attributes['nomor_seri_terjual'])) {
            return explode(',', $this->attributes['nomor_seri_terjual']);
        }
        return [];
    }

    public function setNomorSeriTerjualArrayAttribute(array $serials = null)
    {
        if (!empty($serials)) {
            $this->attributes['nomor_seri_terjual'] = implode(',', array_unique($serials)); // Pastikan unik
        } else {
            $this->attributes['nomor_seri_terjual'] = null;
        }
    }
}