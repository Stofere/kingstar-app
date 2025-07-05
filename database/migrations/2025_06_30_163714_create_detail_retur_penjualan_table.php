<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailReturPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('detail_retur_penjualan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_retur_penjualan')->constrained('retur_penjualan')->onDelete('cascade'); // Link ke header retur
        $table->foreignId('id_detail_penjualan_asal')->constrained('detail_penjualan')->onDelete('cascade'); // Link ke item di nota penjualan asal

        $table->unsignedInteger('jumlah_retur');
        $table->string('nomor_seri_diretur')->nullable();
        $table->string('alasan_retur');
        $table->string('tindakan_lanjut');
        $table->text('catatan_pelanggan')->nullable()->comment('Catatan per item');

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
        Schema::dropIfExists('detail_retur_penjualan');
    }
}
