<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiwayatPergerakanStokTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('riwayat_pergerakan_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_produk')->constrained('produk')->comment('Produk yang bergerak');
            $table->foreignId('id_stok_barang_terkait')->nullable()->constrained('stok_barang')->onDelete('set null')->comment('Batch fisik yang terpengaruh');
            $table->string('nomor_seri')->nullable()->index()->comment('Nomor seri spesifik jika ada');
            
            $table->string('tipe_transaksi')->index()->comment('PENERIMAAN_PO, PENJUALAN, RETUR_PELANGGAN, dll.');
            
            $table->integer('jumlah_masuk')->default(0);
            $table->integer('jumlah_keluar')->default(0);
            $table->integer('saldo_setelah_transaksi')->comment('Snapshot saldo total produk setelah transaksi ini');
            
            $table->unsignedBigInteger('id_referensi')->nullable()->comment('ID dari nota/dokumen sumber');
            $table->string('tipe_referensi')->nullable()->comment('Model dari dokumen sumber (polymorphic)');
            
            $table->dateTime('tanggal_transaksi')->index();
            $table->text('keterangan')->nullable();
            $table->foreignId('id_pengguna')->constrained('pengguna')->comment('User yang memproses');
            
            $table->timestamps();

            $table->index(['id_referensi', 'tipe_referensi']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('riwayat_pergerakan_stok');
    }
}