<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DetailPenjualanAlokasiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_penjualan_stok_alokasi', function (Blueprint $table) {
            $table->id(); // BIGINT AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('id_detail_penjualan');
            $table->unsignedBigInteger('id_stok_barang'); // Batch spesifik yg dijual
            $table->integer('jumlah_diambil');
            $table->timestamps(); // created_at dan updated_at

            $table->foreign('id_detail_penjualan')->references('id')->on('detail_penjualan')->onDelete('cascade');
            $table->foreign('id_stok_barang')->references('id')->on('stok_barang')->onDelete('restrict'); // atau cascade jika sesuai
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_penjualan_stok_alokasi');
    }
}
