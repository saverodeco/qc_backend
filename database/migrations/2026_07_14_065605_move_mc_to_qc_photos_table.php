<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MC sekarang diinput per foto (banyak nilai per IML), bukan satu nilai
     * di level receiving_iml. Kolom mc lama dihapus dari receiving_iml,
     * digantikan average_mc (nullable) sebagai ringkasan hasil hitung
     * rata-rata dari seluruh foto, dipakai untuk tampilan list/laporan
     * supaya tidak perlu JOIN + AVG() tiap kali render.
     *
     * IMP dan OT TETAP satu nilai per IML, tidak berubah.
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->dropColumn('mc');
            $table->decimal('average_mc', 5, 2)->nullable()->after('nama_material');
        });

        Schema::table('qc_photos', function (Blueprint $table) {
            $table->decimal('mc', 5, 2)->nullable()->after('urutan_foto');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->decimal('mc', 5, 2)->nullable()->after('nama_material');
            $table->dropColumn('average_mc');
        });

        Schema::table('qc_photos', function (Blueprint $table) {
            $table->dropColumn('mc');
        });
    }
};