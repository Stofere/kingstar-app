<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProdukTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            // Foreign key ke merk (nullable, set null on delete)
            $table->foreignId('id_merk')->nullable()->constrained('merk')->onDelete('set null');
            $table->string('kode_produk', 100)->nullable()->unique();
            $table->string('nama')->index();
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_jual_standart', 15, 2)->nullable()->comment('Harga patokan awal');
            $table->string('gambar')->nullable();
            $table->string('satuan', 50)->default('PCS');
            $table->boolean('memiliki_serial')->default(false)->comment('1=Ya, 0=Tidak');
            $table->unsignedSmallInteger('durasi_garansi_standar_bulan')->nullable()->comment('Durasi garansi pelanggan dlm bulan (jika ada)');
            $table->boolean('status')->default(true)->comment('1=Aktif, 0=Tidak Aktif');
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
        Schema::dropIfExists('produk');
    }
}
