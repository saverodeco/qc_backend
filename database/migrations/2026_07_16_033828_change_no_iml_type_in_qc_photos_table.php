<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * receiving_iml.no_iml sekarang integer (mengikuti skema perusahaan),
     * jadi qc_photos.no_iml (yang dipakai buat relasi belongsTo/hasMany)
     * harus ikut berubah tipe supaya query where('no_iml', ...) tetap
     * konsisten tipenya.
     *
     * Pakai raw SQL + USING clause karena Postgres menolak auto-cast
     * varchar -> integer lewat Blueprint::change() biasa.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE qc_photos ALTER COLUMN no_iml TYPE integer USING no_iml::integer');

        Schema::table('qc_photos', function (Blueprint $table) {
            $table->foreign('no_iml')->references('no_iml')->on('receiving_iml');
        });
    }

    public function down(): void
    {
        Schema::table('qc_photos', function (Blueprint $table) {
            $table->dropForeign(['no_iml']);
        });

        DB::statement('ALTER TABLE qc_photos ALTER COLUMN no_iml TYPE varchar(255) USING no_iml::varchar');
    }
};