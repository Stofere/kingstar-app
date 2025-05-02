<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStokBarangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stok_barang', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_produk')->constrained('produk')->onDelete('restrict');
            $table->foreignId('id_detail_pembelian')->nullable()->constrained('detail_pembelian')->onDelete('set null');
            $table->foreignId('id_supplier')->nullable()->constrained('supplier')->onDelete('set null')->comment('Supplier batch ini (jika konsinyasi/diketahui)');
            $table->decimal('harga_beli', 15, 2)->comment('Harga beli satuan saat batch ini diterima');
            $table->unsignedInteger('jumlah')->comment('Jumlah fisik TERSISA dalam batch ini');
            $table->dateTime('diterima_at')->index()->comment('Waktu barang fisik diterima (Kunci FIFO)');
            $table->string('tipe_garansi', 50)->default('NONE')->index()->comment('NONE, RESMI, SELF_SERVICE');
            $table->date('garansi_berakhir_at')->nullable()->comment('Tgl garansi supplier/internal (Info Saja, bukan utk pelanggan)');
            $table->string('tipe_stok', 50)->default('REGULER')->index()->comment('REGULER, KONSINYASI');
            $table->string('lokasi', 50)->index()->comment('GUDANG, TOKO');
            $table->string('kondisi', 100)->default('BAIK')->index()->comment('BAIK, RUSAK, SERVIS, KOMPLAIN_SUPPLIER, dll');
            // Foreign key untuk alokasi Pre-Order (nullable, set null on delete)
            $table->foreignId('id_penjualan_alokasi')->nullable()->constrained('penjualan')->onDelete('set null')->comment('FK ke penjualan jika dialokasikan u/ Pre-Order');
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
        Schema::dropIfExists('stok_barang');
    }
}
