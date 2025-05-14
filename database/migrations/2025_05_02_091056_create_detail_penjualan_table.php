<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_penjualan')->constrained('penjualan')->onDelete('cascade'); // FK ke penjualan
            $table->foreignId('id_produk')->constrained('produk')->onDelete('restrict'); // FK ke produk master
            // Snapshot data
            $table->string('nama_produk_snapshot', 255)->comment('Snapshot nama produk saat transaksi');
            $table->string('kode_produk_snapshot', 100)->comment('Snapshot kode produk saat transaksi'); // Tidak unique, karena snapshot
            $table->unsignedInteger('jumlah')->comment('Jumlah dijual dari batch ini');
            $table->decimal('harga_jual', 15, 2)->comment('Harga jual satuan final (nego)');
            $table->decimal('subtotal', 15, 2)->comment('Total jumlah produk ini dalam penjualan');
            $table->string('nomor_seri_terjual')->nullable()->index()->comment('Nomor seri spesifik (jika produk berserial)');
            $table->string('status_bayar_konsinyasi', 50)->default('BELUM_RELEVAN')->index()->comment('Status bayar konsinyasi: BELUM_RELEVAN, BELUM_DIBAYAR_SUPPLIER, SUDAH_DIBAYAR_SUPPLIER');
            $table->date('customer_garansi_mulai_at')->nullable()->comment('Tanggal mulai garansi pelanggan');
            $table->date('customer_garansi_berakhir_at')->nullable()->index()->comment('Tanggal berakhir garansi pelanggan');
            $table->text('catatan')->nullable()->comment('Catatan tambahan');
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
        // Drop the dependent table first
        Schema::dropIfExists('detail_penjualan_stok_alokasi');
        // Then drop this table
        Schema::dropIfExists('detail_penjualan');
    }
}
