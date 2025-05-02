<?php

namespace Database\Factories;

use App\Models\Merk; // Import Merk
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition()
    {
        $namaProduk = $this->faker->randomElement(['Mixer', 'Speaker Aktif', 'Speaker Pasif', 'Power Amplifier', 'Microphone Kabel', 'Microphone Wireless', 'Kabel XLR', 'Stand Mic', 'Moving Head Beam', 'PAR LED', 'Smoke Machine', 'Hardcase']);
        $memilikiSerial = $this->faker->boolean(60); // 60% kemungkinan punya serial

        return [
            // Ambil ID Merk secara acak dari merk yang sudah ada
            'id_merk' => Merk::inRandomOrder()->first()->id ?? Merk::factory(), // Jika belum ada merk, buat baru
            'kode_produk' => strtoupper(Str::random(3)) . $this->faker->unique()->numerify('#####'),
            'nama' => $namaProduk . ' ' . $this->faker->words(2, true),
            'deskripsi' => $this->faker->optional()->sentence(10),
            'harga_jual_standart' => $this->faker->numberBetween(100000, 15000000),
            'satuan' => 'PCS',
            'memiliki_serial' => $memilikiSerial,
            'durasi_garansi_standar_bulan' => $memilikiSerial ? $this->faker->randomElement([6, 12, 24]) : $this->faker->randomElement([0, 1, null]), // Garansi lebih mungkin jika ada serial
            'status' => true,
        ];
    }

     /**
     * Indicate that the product has a serial number.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function berserial()
    {
        return $this->state(function (array $attributes) {
            return [
                'memiliki_serial' => true,
                'durasi_garansi_standar_bulan' => $this->faker->randomElement([6, 12, 24]),
            ];
        });
    }

     /**
     * Indicate that the product does not have a serial number.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function tanpaSerial()
    {
        return $this->state(function (array $attributes) {
            return [
                'memiliki_serial' => false,
                'durasi_garansi_standar_bulan' => $this->faker->randomElement([0, 1, null]),
            ];
        });
    }
}