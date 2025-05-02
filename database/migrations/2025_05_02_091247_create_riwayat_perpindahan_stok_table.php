<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiwayatPerpindahanStokTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('riwayat_perpindahan_stok', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('id_stok_barang')->constrained('stok_barang')->onDelete('restrict')->comment('Batch yg dipindah');
            $table->unsignedInteger('jumlah')->comment('Jumlah yg dipindah (biasanya = jumlah batch saat itu)');
            $table->string('dari_lokasi', 50)->index();
            $table->string('ke_lokasi', 50)->index();
            $table->dateTime('dipindahkan_at')->index();
            $table->foreignId('id_pengguna')->constrained('pengguna')->onDelete('restrict');
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
        Schema::dropIfExists('riwayat_perpindahan_stok');
    }
}
