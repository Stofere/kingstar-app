<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePembelianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pembelian', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_supplier')->constrained('supplier')->onDelete('restrict');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
            $table->string('nomor_pembelian', 100)->nullable()->unique()->comment('Nomor nota internal toko jika ada');
            $table->string('nomor_faktur_supplier', 100)->nullable()->comment('Nomor nota dari supplier jika ada');
            $table->date('tanggal_pembelian')->index();
            $table->decimal('total_harga', 15, 2)->default(0.00)->comment('Total estimasi/final dari detail');
            $table->string('metode_pembayaran', 50)->nullable()->comment('TUNAI, TRANSFER BCA, dll');
            $table->string('status_pembayaran', 50)->default('BELUM_LUNAS')->index()->comment('BELUM_LUNAS, LUNAS, JATUH_TEMPO');
            $table->date('dibayar_at')->nullable()->comment('Tanggal lunas bayar ke supplier');
            $table->string('status_pembelian', 50)->default('DRAFT')->index()->comment('DRAFT, DIPESAN, PENGIRIMAN, TIBA_SEBAGIAN, SELESAI, DIBATALKAN');
            $table->text('catatan')->nullable();
            $table->boolean('status')->default(true)->comment('Soft delete flag');
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
        Schema::dropIfExists('pembelian');
    }
}
