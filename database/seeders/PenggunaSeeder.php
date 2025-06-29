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
        // Buat User Admin Utama
        Pengguna::create([
            'nama' => 'Andreas Wijaya',
            'username' => 'admin',
            'password' => Hash::make('admin123'), 
            'role' => 'ADMIN',
            'status' => true,
        ]);

        Pengguna::create([
            'nama' => 'Roger Jeremy',
            'username' => 'kasir1',
            'password' => Hash::make('kasir123'), 
            'role' => 'KASIR',
            'status' => true,
        ]);

         Pengguna::create([
            'nama' => 'Johan William',
            'username' => 'gudang1',
            'password' => Hash::make('gudang123'),
            'role' => 'GUDANG',
            'status' => true,
        ]);


    }
}