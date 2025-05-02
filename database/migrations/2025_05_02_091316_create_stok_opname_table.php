<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStokOpnameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_opname')->index();
            $table->string('lokasi', 50)->nullable()->index()->comment('Jika opname per lokasi, GUDANG/TOKO');
            // Foreign keys
            $table->foreignId('id_pengguna_mulai')->constrained('pengguna')->onDelete('restrict');
            $table->foreignId('id_pengguna_selesai')->nullable()->constrained('pengguna')->onDelete('restrict');
            $table->string('status', 50)->default('BERJALAN')->index()->comment('BERJALAN, SELESAI, DIBATALKAN');
            $table->text('catatan')->nullable();
            $table->timestamp('started_at')->useCurrent(); // Menggunakan default CURRENT_TIMESTAMP
            $table->timestamp('finished_at')->nullable();
            // Tidak menggunakan created_at/updated_at standar Laravel
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stok_opname');
    }
}
