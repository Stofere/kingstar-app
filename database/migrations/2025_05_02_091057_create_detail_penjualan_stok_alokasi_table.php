<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_penjualan_stok_alokasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_detail_penjualan');
            $table->unsignedBigInteger('id_stok_barang'); // Batch stok yang digunakan/dialokasikan
            $table->integer('jumlah_diambil');     // Jumlah yang diambil/dialokasikan dari batch ini

            // Kolom untuk mendukung pra-alokasi dan detail serial
            $table->text('nomor_seri_terkait')->nullable(); // Comma-separated serials yang terkait dengan alokasi/pengambilan ini
            $table->string('tipe_alokasi', 50)->default('STOK_KELUAR_BIASA');
            // Kemungkinan nilai untuk tipe_alokasi:
            // - 'STOK_KELUAR_BIASA': Untuk penjualan biasa, stok langsung keluar.
            // - 'DIALOKASIKAN_PESANAN': Untuk Pesan Barang, stok di-booking oleh Admin.
            // - 'STOK_KELUAR_PESANAN': Untuk Pesan Barang, saat barang diambil pelanggan (final).
            // - 'DIBATALKAN_PESANAN': Jika pra-alokasi pesanan dibatalkan.

            // Opsional: Kolom audit untuk siapa dan kapan pra-alokasi dilakukan
            $table->unsignedBigInteger('dialokasikan_oleh')->nullable(); // User Admin yang melakukan pra-alokasi
            $table->timestamp('dialokasikan_at')->nullable();      // Waktu pra-alokasi dibuat

            $table->timestamps(); // created_at and updated_at

            // Foreign Keys
            $table->foreign('id_detail_penjualan')
                  ->references('id')
                  ->on('detail_penjualan')
                  ->onDelete('cascade');

            $table->foreign('id_stok_barang')
                  ->references('id')
                  ->on('stok_barang')
                  ->onDelete('restrict'); // Jaga agar batch tidak dihapus jika masih terkait

            $table->foreign('dialokasikan_oleh')
                  ->references('id')
                  ->on('pengguna')
                  ->onDelete('set null');

            // Index untuk performa query
            $table->index(['id_detail_penjualan', 'id_stok_barang'], 'idx_dpsa_detail_dan_stok'); // Penamaan index yang lebih jelas
            $table->index('id_stok_barang', 'idx_dpsa_stok_barang'); // Index terpisah untuk id_stok_barang
            $table->index('tipe_alokasi', 'idx_dpsa_tipe_alokasi');   // Index untuk tipe_alokasi
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_penjualan_stok_alokasi');
    }
};