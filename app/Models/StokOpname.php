<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;

    protected $table = 'stok_opname';

    // Nonaktifkan timestamps default Laravel karena ada started_at/finished_at
    public $timestamps = false;

    protected $fillable = [
        'tanggal_opname',
        'lokasi',
        'id_pengguna_mulai',
        'id_pengguna_selesai',
        'status',
        'catatan',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
        'started_at' => 'timestamp',
        'finished_at' => 'timestamp',
    ];

    // Relasi: StokOpname belongs to Pengguna (yang memulai)
    public function penggunaMulai()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_mulai');
    }

    // Relasi: StokOpname belongs to Pengguna (yang menyelesaikan)
    public function penggunaSelesai()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_selesai');
    }

    // Relasi: StokOpname has many DetailStokOpname
    public function detailStokOpname()
    {
        return $this->hasMany(DetailStokOpname::class, 'id_stok_opname');
    }

    // Relasi: StokOpname bisa menghasilkan banyak PenyesuaianStok
    public function penyesuaianStok()
    {
        return $this->hasMany(PenyesuaianStok::class, 'id_stok_opname');
    }
}