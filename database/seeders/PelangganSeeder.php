<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding Pelanggan...');

        Pelanggan::create([
            'nama' => 'Pelanggan Umum',
        ]);
    }
}