<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogNomorSeriTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_nomor_seri', function (Blueprint $table) {
            $table->id(); // BIGINT AUTO_INCREMENT PRIMARY KEY
            $table->foreignId('id_produk')->constrained('produk')->onDelete('cascade'); // Cascade jika produk dihapus? Atau restrict? Pilih cascade jika log serial tidak relevan tanpa produk.
            $table->foreignId('id_stok_barang_asal')->nullable()->constrained('stok_barang')->onDelete('set null')->comment('FK ke stok_barang.id (batch asal saat DITERIMA)');
            $table->string('nomor_seri')->comment('Nomor seri fisik barang');
            $table->string('status_log', 50)->comment('DITERIMA, TERJUAL, DIRETUR_PELANGGAN, DIRETUR_SUPPLIER, RUSAK, HILANG, DITEMUKAN, KOREKSI, DIPINDAHKAN');
            // Kolom untuk relasi polymorphic
            $table->unsignedBigInteger('id_referensi')->nullable();
            $table->string('tipe_referensi')->nullable();
            $table->dateTime('tanggal_status')->comment('Waktu kejadian status ini');
            $table->text('catatan')->nullable();
            $table->timestamps(); // Menggunakan timestamps() standar Laravel

            // Index untuk pencarian nomor seri dan polymorphic
            $table->index(['id_produk', 'nomor_seri']); // Index komposit untuk pencarian serial per produk
            $table->unique(['id_produk', 'nomor_seri']); // Membuat nomor seri unik per produk
            $table->index(['id_referensi', 'tipe_referensi']);
            $table->index('status_log');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_nomor_seri');
    }
}
