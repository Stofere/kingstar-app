<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('detail_penjualan_stok_alokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_detail_penjualan')->constrained('detail_penjualan')->onDelete('cascade');
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict');
            $table->integer('jumlah_diambil');
            $table->timestamps();

            // Index untuk performa query
            $table->index(['id_detail_penjualan', 'id_stok_barang'], 'idx_dpsa_detail_stok');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_penjualan_stok_alokasi');
    }
}; 