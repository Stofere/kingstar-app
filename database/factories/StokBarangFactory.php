<?php

namespace Database\Factories;

use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\Supplier; // Import Supplier
use Illuminate\Database\Eloquent\Factories\Factory;

class StokBarangFactory extends Factory
{
    protected $model = StokBarang::class;

    public function definition()
    {
        $produk = Produk::inRandomOrder()->first() ?? Produk::factory()->create();
        $hargaBeli = $produk->harga_jual_standart ? $produk->harga_jual_standart * $this->faker->randomFloat(2, 0.6, 0.85) : $this->faker->numberBetween(50000, 10000000); // Estimasi harga beli
        $diterima = $this->faker->dateTimeBetween('-1 year', 'now'); // Tanggal terima dalam 1 tahun terakhir
        $tipeGaransi = $this->faker->randomElement(['NONE', 'RESMI', 'SELF_SERVICE']);
        $tipeStok = $this->faker->randomElement(['REGULER', 'REGULER', 'REGULER', 'KONSINYASI']); // Lebih banyak reguler

        return [
            'id_produk' => $produk->id,
            // 'id_detail_pembelian' => null, // Biasanya null untuk seed awal, diisi saat seeding pembelian
            'id_supplier' => ($tipeStok == 'KONSINYASI') ? (Supplier::inRandomOrder()->first()->id ?? Supplier::factory()) : null,
            'harga_beli' => $hargaBeli,
            'jumlah' => $this->faker->numberBetween(1, 50),
            'diterima_at' => $diterima,
            'tipe_garansi' => $tipeGaransi,
            // 'garansi_berakhir_at' => ($tipeGaransi != 'NONE') ? $this->faker->dateTimeBetween($diterima, '+2 years')->format('Y-m-d') : null, // Info garansi internal (opsional)
            'garansi_berakhir_at' => null, // Biarkan null dulu sesuai model
            'tipe_stok' => $tipeStok,
            'lokasi' => $this->faker->randomElement(['GUDANG', 'TOKO']),
            'kondisi' => 'BAIK',
            'id_penjualan_alokasi' => null,
        ];
    }

     /**
     * Indicate that the stock is consignment.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function konsinyasi()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipe_stok' => 'KONSINYASI',
                'id_supplier' => Supplier::inRandomOrder()->first()->id ?? Supplier::factory(),
            ];
        });
    }

     /**
     * Indicate that the stock is regular.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function reguler()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipe_stok' => 'REGULER',
                'id_supplier' => null, // Supplier bisa null untuk reguler
            ];
        });
    }
}
