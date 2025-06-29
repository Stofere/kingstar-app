<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;

    protected $table = 'stok_opname';

    // Izinkan semua field untuk diisi, atau sesuaikan dengan kebutuhan
    protected $guarded = ['id'];
    
    // Matikan timestamp default Laravel jika Anda menggunakan started_at/finished_at
    public $timestamps = false;

    // Casting tipe data agar lebih mudah diolah
    protected $casts = [
        'tanggal_opname' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
    
    /**
     * Relasi ke Pengguna yang memulai sesi opname.
     */
    public function penggunaMulai()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_mulai');
    }

    /**
     * Relasi ke Pengguna yang menyelesaikan sesi opname.
     */
    public function penggunaSelesai()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_selesai');
    }

    /**
     * Relasi ke detail-detail item opname.
     */
    public function detailStokOpname()
    {
        return $this->hasMany(DetailStokOpname::class, 'id_stok_opname');
    }

}