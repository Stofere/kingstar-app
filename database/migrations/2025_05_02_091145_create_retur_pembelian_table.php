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
            $table->string('nomor_retur', 100)->unique()->comment('Nomor unik untuk proses retur pembelian ini');
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict')->comment('Batch stok spesifik yg diretur ke supplier');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict')->comment('User (Admin) yg memproses retur');
            $table->unsignedInteger('jumlah_retur');
            $table->string('nomor_seri_diretur')->nullable()->comment('Nomor seri spesifik yg diretur (comma-separated jika >1)');
            $table->string('alasan_retur')->nullable()->comment('Alasan mengapa barang ini diretur ke supplier');
            $table->text('catatan_ke_supplier')->nullable()->comment('Catatan spesifik untuk supplier terkait item/proses retur ini'); 
            $table->string('tindakan_lanjut_supplier', 100)->nullable()->comment('Status tindak lanjut dari supplier (MENUNGGU_PENGGANTIAN, dll.)'); 
            $table->text('catatan_internal_retur')->nullable()->comment('Catatan internal oleh staf terkait proses retur ini secara keseluruhan');
            $table->timestamp('tanggal_retur')->useCurrent()->index()->comment('Tanggal dan waktu retur diproses/dikirim');
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
