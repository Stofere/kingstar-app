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
            // Foreign keys
            $table->foreignId('id_detail_penjualan')->constrained('detail_penjualan')->onDelete('cascade')->comment('Item penjualan asal yg diretur'); // Cascade jika detail dihapus
            $table->string('nomor_seri_diretur')->nullable()->comment('Nomor seri spesifik (jika produk berserial)');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict')->comment('User yg memproses retur');
            $table->unsignedInteger('jumlah_retur');
            $table->text('alasan')->nullable()->comment('Alasan dari pelanggan');
            $table->text('catatan_pelanggan')->nullable()->comment('Catatan tambahan dari pelanggan');
            $table->string('status_retur', 50)->default('DIAJUKAN')->index()->comment('DIAJUKAN, DISETUJUI, DITOLAK, SELESAI');
            $table->date('tanggal_retur')->index();
            $table->string('tindakan_lanjut', 100)->nullable()->comment('TUKAR_BARANG, KEMBALI_KE_STOK_BAIK, KEMBALI_KE_STOK_RUSAK, SERVIS, KOMPLAIN_SUPPLIER');
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
