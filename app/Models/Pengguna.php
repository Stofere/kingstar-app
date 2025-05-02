<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// Gunakan Authenticatable jika model ini untuk login Laravel
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; // Jika menggunakan notifikasi
use Laravel\Sanctum\HasApiTokens; // Jika menggunakan Sanctum untuk API

class Pengguna extends Authenticatable // Ganti extends Model jika tidak untuk auth
{
    use HasFactory;

    protected $table = 'pengguna'; // Tentukan nama tabel secara eksplisit

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        // 'email_verified_at' => 'datetime', // Jika ada
    ];

    // Relasi: Pengguna bisa melakukan banyak pembelian
    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'id_pengguna');
    }

    // Relasi: Pengguna bisa melakukan banyak penjualan
    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_pengguna');
    }

    // Relasi: Pengguna bisa memproses banyak retur pembelian
    public function returPembelian()
    {
        return $this->hasMany(ReturPembelian::class, 'id_pengguna');
    }

    // Relasi: Pengguna bisa memproses banyak retur penjualan
    public function returPenjualan()
    {
        return $this->hasMany(ReturPenjualan::class, 'id_pengguna');
    }

    // Relasi: Pengguna bisa melakukan banyak perpindahan stok
    public function riwayatPerpindahanStok()
    {
        return $this->hasMany(RiwayatPerpindahanStok::class, 'id_pengguna');
    }

    // Relasi: Pengguna bisa memulai banyak stok opname
    public function stokOpnameDimulai()
    {
        return $this->hasMany(StokOpname::class, 'id_pengguna_mulai');
    }

    // Relasi: Pengguna bisa menyelesaikan banyak stok opname
    public function stokOpnameDiselesaikan()
    {
        return $this->hasMany(StokOpname::class, 'id_pengguna_selesai');
    }

    // Relasi: Pengguna bisa melakukan banyak penyesuaian stok
    public function penyesuaianStok()
    {
        return $this->hasMany(PenyesuaianStok::class, 'id_pengguna');
    }
}