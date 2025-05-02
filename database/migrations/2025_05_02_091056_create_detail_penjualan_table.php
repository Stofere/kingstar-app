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
            $table->foreignId('id_penjualan')->constrained('penjualan')->onDelete('cascade'); // Cascade jika penjualan dihapus
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict')->comment('Batch spesifik yg dijual');
            $table->unsignedInteger('jumlah')->comment('Jumlah dijual dari batch ini');
            $table->decimal('harga_jual', 15, 2)->comment('Harga jual satuan final (nego)');
            $table->string('nomor_seri_terjual')->nullable()->index()->comment('Nomor seri spesifik (jika produk berserial)');
            $table->string('status_bayar_konsinyasi', 50)->default('BELUM_RELEVAN')->index()->comment('BELUM_RELEVAN, BELUM_DIBAYAR_SUPPLIER, SUDAH_DIBAYAR_SUPPLIER');
            $table->date('customer_garansi_mulai_at')->nullable()->comment('Tanggal mulai garansi pelanggan');
            $table->date('customer_garansi_berakhir_at')->nullable()->index()->comment('Tanggal berakhir garansi pelanggan');
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
        Schema::dropIfExists('detail_penjualan');
    }
}
