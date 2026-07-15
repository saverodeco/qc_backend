<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revert: MC kembali jadi SATU nilai per IML (sama seperti IMP/OT),
     * bukan per foto lagi. average_mc di receiving_iml diganti balik
     * jadi mc, dan kolom mc di qc_photos dihapus.
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->decimal('mc', 5, 2)->nullable()->after('nama_material');
            $table->dropColumn('average_mc');
        });

        Schema::table('qc_photos', function (Blueprint $table) {
            $table->dropColumn('mc');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->decimal('average_mc', 5, 2)->nullable()->after('nama_material');
            $table->dropColumn('mc');
        });

        Schema::table('qc_photos', function (Blueprint $table) {
            $table->decimal('mc', 5, 2)->nullable()->after('urutan_foto');
        });
    }
};