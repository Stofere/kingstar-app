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
            $table->string('nomor_retur', 100)->unique(); 
            $table->foreignId('id_penjualan_asal')->constrained('penjualan')->onDelete('cascade'); 
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
            $table->timestamp('tanggal_retur')->index();
            $table->string('status_retur', 50)->default('MENUNGGU_PROSES_ADMIN'); 
            $table->text('catatan_internal_retur')->nullable()->comment('Catatan global untuk seluruh retur ini');
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
