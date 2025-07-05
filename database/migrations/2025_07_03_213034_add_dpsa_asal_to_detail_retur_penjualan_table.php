<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDpsaAsalToDetailReturPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            $table->foreignId('id_dpsa_asal')->nullable()->after('id_detail_penjualan_asal')
                  ->constrained('detail_penjualan_stok_alokasi')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_retur_penjualan', function (Blueprint $table) {
            //
        });
    }
}
