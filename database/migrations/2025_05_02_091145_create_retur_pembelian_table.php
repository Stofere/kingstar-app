<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturPembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur', 100)->unique(); 
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
            $table->foreignId('id_supplier_tujuan')->constrained('supplier')->onDelete('restrict');
            $table->timestamp('tanggal_retur')->index();
            $table->text('catatan_internal_retur')->nullable()->comment('Catatan global untuk seluruh retur ini');
            $table->string('status', 50)->default('PROSES'); // Status global: PROSES, SELESAI, DIBATALKAN
            
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
        Schema::dropIfExists('retur_pembelian');
    }
}
