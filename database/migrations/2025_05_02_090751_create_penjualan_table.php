<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_pelanggan')->nullable()->constrained('pelanggan')->onDelete('set null');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
            $table->string('nomor_penjualan', 100)->unique()->comment('Nomor nota/transaksi unik toko');
            $table->dateTime('tanggal_penjualan')->index()->comment('Waktu transaksi dibuat/selesai');
            $table->decimal('total_harga', 15, 2)->default(0.00);
            $table->string('metode_pembayaran', 50)->nullable()->comment('TUNAI, QRIS, TRANSFER BCA');
            $table->string('kanal_transaksi', 50)->default('TOKO')->comment('TOKO, TOKOPEDIA, SHOPEE');
            $table->string('tipe_transaksi', 50)->default('BIASA')->comment('BIASA, PRE_ORDER');
            $table->decimal('uang_muka', 15, 2)->nullable()->comment('Jika PRE_ORDER');
            $table->decimal('sisa_pembayaran', 15, 2)->nullable()->comment('Jika PRE_ORDER');
            $table->date('estimasi_kirim_at')->nullable()->comment('Jika PRE_ORDER');
            $table->string('status_pembayaran', 50)->index()->comment('LUNAS, BELUM_LUNAS, DP');
            $table->date('dibayar_at')->nullable()->comment('Tanggal lunas');
            $table->string('status_penjualan', 50)->default('PROSES')->index()->comment('PROSES, MENUNGGU_BARANG, SIAP_DIKIRIM, PENGIRIMAN, SELESAI, DIBATALKAN, STOK_TIDAK_CUKUP, MENUNGGU_PELUNASAN');
            $table->dateTime('tanggal_pengiriman')->nullable();
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
        Schema::dropIfExists('penjualan');
    }
}
