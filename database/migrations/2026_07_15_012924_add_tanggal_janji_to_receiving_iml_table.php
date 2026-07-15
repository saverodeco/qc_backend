<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tanggal_janji = jadwal kedatangan yang dijanjikan (dipakai untuk
     * menentukan tanggal tampil di layar QC).
     * tanggal_kedatangan = tanggal AKTUAL kendaraan tiba di pabrik
     * (bisa berbeda dari tanggal_janji, kendaraan bisa datang lebih
     * cepat atau lebih lambat dari jadwal).
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->date('tanggal_janji')->nullable()->after('no_iml');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->dropColumn('tanggal_janji');
        });
    }
};