<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailReturPembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_retur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_retur_pembelian')->constrained('retur_pembelian')->onDelete('cascade');
            
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict');
            $table->unsignedInteger('jumlah_retur');
            $table->string('nomor_seri_diretur')->nullable();
            $table->string('alasan_retur');
            $table->string('tindakan_lanjut_supplier');
            $table->text('catatan_ke_supplier')->nullable();

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
        Schema::dropIfExists('detail_retur_pembelian');
    }
}
