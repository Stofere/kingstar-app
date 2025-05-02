<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailPembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_pembelian', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_pembelian')->constrained('pembelian')->onDelete('cascade'); // Cascade delete jika pembelian dihapus
            $table->foreignId('id_produk')->constrained('produk')->onDelete('restrict');
            $table->unsignedInteger('jumlah')->comment('Jumlah dipesan');
            $table->decimal('harga_beli', 15, 2)->comment('Harga beli satuan saat pesan');
            $table->unsignedInteger('jumlah_diterima')->default(0)->comment('Jumlah kumulatif diterima');
            $table->text('catatan')->nullable();
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
        Schema::dropIfExists('detail_pembelian');
    }
}
