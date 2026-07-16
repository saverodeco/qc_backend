<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 4 timestamp proses kendaraan (bukan bagian dari skema
     * IMLQCInspection Header perusahaan — kemungkinan besar berasal dari
     * sistem gate/timbangan terpisah, tapi perlu ditampilkan di layar QC
     * ini juga).
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->timestamp('jam_check_in')->nullable()->after('tanggal_kedatangan');
            $table->timestamp('jam_mulai_bongkar')->nullable()->after('jam_check_in');
            $table->timestamp('jam_selesai_bongkar')->nullable()->after('jam_mulai_bongkar');
            $table->timestamp('jam_check_out')->nullable()->after('jam_selesai_bongkar');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->dropColumn([
                'jam_check_in',
                'jam_mulai_bongkar',
                'jam_selesai_bongkar',
                'jam_check_out',
            ]);
        });
    }
};