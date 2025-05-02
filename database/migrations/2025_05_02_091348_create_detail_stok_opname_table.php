<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailStokOpnameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_stok_opname', function (Blueprint $table) {
            $table->id(); // BIGINT AUTO_INCREMENT PRIMARY KEY
            // Foreign keys
            $table->foreignId('id_stok_opname')->constrained('stok_opname')->onDelete('cascade'); // Cascade jika opname dihapus
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('cascade')->comment('Batch yg dihitung'); // Cascade jika batch dihapus? Atau restrict? Pilih cascade jika detail opname tidak relevan tanpa batchnya.
            $table->integer('jumlah_sistem')->comment('Jumlah di sistem saat opname mulai');
            $table->integer('jumlah_fisik')->nullable()->comment('Jumlah fisik hasil hitung (diisi user)');
            $table->integer('selisih')->nullable()->comment('jumlah_fisik - jumlah_sistem (dihitung sistem)');
            $table->text('catatan')->nullable();
            $table->timestamps(); // Menggunakan timestamps() standar Laravel
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_stok_opname');
    }
}
