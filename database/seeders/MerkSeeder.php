<?php 

namespace Database\Seeders;

use App\Models\Merk;
use Illuminate\Database\Seeder;


class MerkSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding Merk...');
        Merk::create([
            'nama' => 'JBL',
        ]);

        Merk::create([
            'nama' => 'Huper',
        ]);

        Merk::create([
            'nama' => 'XLR',
        ]);


        Merk::create([
            'nama' => 'Shure',
        ]);

        Merk::create([
            'nama' => 'Behringer',
        ]);
    }
}