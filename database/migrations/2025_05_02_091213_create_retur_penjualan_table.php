<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('retur_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur', 100)->unique()->comment('Nomor unik untuk proses retur ini, bisa group beberapa item');
            $table->foreignId('id_detail_penjualan')->constrained('detail_penjualan')->onDelete('cascade')->comment('Item penjualan asal yg diretur');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict')->comment('User (Kasir/Admin) yg memproses retur');
            $table->unsignedInteger('jumlah_retur');
            $table->string('nomor_seri_diretur')->nullable()->comment('Nomor seri spesifik yg diretur (comma-separated jika >1 untuk item yg sama, meskipun jarang)');
            $table->string('alasan_retur')->nullable()->comment('Alasan dari pelanggan atau pilihan Kasir'); 
            $table->text('catatan_pelanggan')->nullable()->comment('Catatan tambahan dari pelanggan terkait item ini');
            $table->string('tindakan_lanjut', 100)->comment('Tindakan setelah barang diterima kembali (misal, DITERIMA_KEMBALI_PERLU_CEK)');
            $table->text('catatan_internal_retur')->nullable()->comment('Catatan internal oleh staf terkait proses retur ini secara keseluruhan');
            $table->timestamp('tanggal_retur')->useCurrent()->index()->comment('Tanggal dan waktu retur diproses'); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retur_penjualan');
    }
}
