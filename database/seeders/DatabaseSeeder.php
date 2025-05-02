<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Memulai Database Seeding...');

        // Panggil seeder dalam urutan dependensi:
        $this->call([
            PenggunaSeeder::class,     // Buat pengguna dulu (terutama admin)
            MasterDataSeeder::class,   // Buat Merk, Supplier, Pelanggan
            ProdukSeeder::class,       // Buat produk (membutuhkan Merk)
            StokBarangSeeder::class,   // Buat stok awal (membutuhkan Produk & Supplier)
            // Tambahkan seeder lain di sini jika ada (misal: Seeder Transaksi Awal)
        ]);

        $this->command->info('Database Seeding Selesai.');
    }
}