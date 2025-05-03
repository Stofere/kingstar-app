<?php

namespace Database\Factories;

use App\Models\Merk;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerkFactory extends Factory
{
    protected $model = Merk::class;

    public function definition()
    {
        // Contoh beberapa merk sound system/lighting
        $brands = ['Yamaha', 'JBL', 'Behringer', 'Shure', 'Soundcraft', 'Huper', 'Martin', 'Robe', 'Clay Paky', 'Neutrik'];
        return [
            // Ambil nama unik dari list atau generate nama perusahaan
            'nama' => $this->faker->unique()->randomElement($brands) . ' ' . $this->faker->randomElement(['Pro', 'Audio', 'Lighting', '']),
            // Atau gunakan: 'nama' => $this->faker->unique()->company(),
        ];
    }
}