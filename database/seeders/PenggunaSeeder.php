<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hapus data lama jika perlu (hati-hati di production!)
        // Pengguna::truncate(); // Nonaktifkan jika tidak ingin menghapus

        // Buat User Admin Utama
        Pengguna::create([
            'nama' => 'Admin Kingstar',
            'username' => 'admin',
            'password' => Hash::make('admin123'), // Ganti dengan password aman
            'role' => 'ADMIN',
            'status' => true,
        ]);

        // Buat User Kasir Contoh
        Pengguna::create([
            'nama' => 'Kasir Satu',
            'username' => 'kasir1',
            'password' => Hash::make('kasir123'), // Ganti dengan password aman
            'role' => 'KASIR',
            'status' => true,
        ]);

         // Buat User Gudang Contoh
         Pengguna::create([
            'nama' => 'Gudang Satu',
            'username' => 'gudang1',
            'password' => Hash::make('gudang123'), // Ganti dengan password aman
            'role' => 'GUDANG',
            'status' => true,
        ]);

        // Buat beberapa user dummy tambahan (opsional)
        Pengguna::factory()->count(5)->create(); // Buat 5 user dengan role acak
    }
}