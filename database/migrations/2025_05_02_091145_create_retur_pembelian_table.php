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
            // Foreign keys
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict')->comment('Batch spesifik yg diretur');
            $table->string('nomor_seri_diretur')->nullable()->comment('Nomor seri spesifik (jika produk berserial)');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict')->comment('User yg memproses retur');
            $table->unsignedInteger('jumlah_retur');
            $table->text('alasan')->nullable();
            $table->string('status_retur', 50)->default('DIAJUKAN')->index()->comment('DIAJUKAN, DISETUJUI, DITOLAK, SELESAI');
            $table->string('tindakan_lanjut', 100)->nullable()->comment('MENUNGGU_PENGGANTIAN, MENUNGGU_REFUND, SELESAI_DIGANTI, SELESAI_DIREFUND, DITOLAK_SUPPLIER');
            $table->date('tanggal_retur')->index();
            $table->text('catatan_supplier')->nullable()->comment('Catatan dari/untuk supplier terkait retur');
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
