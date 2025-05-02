<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenyesuaianStokTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('penyesuaian_stok', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('cascade')->comment('Batch yg disesuaikan'); // Cascade jika batch dihapus? Atau restrict?
            $table->integer('jumlah_penyesuaian')->comment('Jumlah (+/-), misal -1 jika hilang, +1 jika ditemukan');
            $table->string('tipe_penyesuaian', 50)->index()->comment('OPNAME_KURANG, OPNAME_LEBIH, RUSAK, HILANG, DITEMUKAN, KOREKSI_INPUT, RETUR_PELANGGAN_MASUK');
            $table->string('nomor_seri_terkait')->nullable()->index()->comment('Nomor seri spesifik jika penyesuaian per unit serial');
            $table->foreignId('id_stok_opname')->nullable()->constrained('stok_opname')->onDelete('set null')->comment('FK ke stok_opname.id (jika dari opname)');
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
            $table->dateTime('tanggal_penyesuaian');
            $table->text('catatan')->nullable();
            $table->timestamps(); // Hanya created_at yg relevan, updated_at mungkin tidak perlu
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penyesuaian_stok');
    }
}
