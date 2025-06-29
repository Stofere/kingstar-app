<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder; 

class SupplierSeeder extends Seeder 
{
    public function run()
    {
        $this->command->info('Seeding Supplier...');
        Supplier::create([
            'nama' => 'PT. Audio Teknologi',
            'alamat' => 'Jl. Teknologi No. 1, Jakarta',
            'telepon' => '021-12345678',
            'email' => 'info@audio-teknologi.com',
            'status' => true,
        ]);

        Supplier::create([
            'nama' => 'CV. Musik Abadi',
            'alamat' => 'Jl. Musik No. 2, Bandung',
            'telepon' => '022-87654321',
            'email' => 'musik@abadi.com',
            'status' => true,
        ]);

        Supplier::create([
            'nama' => 'UD. Harmoni Suara',
            'alamat' => 'Jl. Harmoni No. 3, Surabaya',
            'telepon' => '031-11223344',
            'email' => 'harmoni@surabaya.com',
            'status' => true,
        ]);
    }
}