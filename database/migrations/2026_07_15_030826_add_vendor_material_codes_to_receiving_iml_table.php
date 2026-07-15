<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - id_vendor + vendor menggantikan kolom supplier lama
     *   (supplier cuma nama, sekarang butuh kode + nama terpisah).
     * - id_material ditambahkan berpasangan dengan nama_material
     *   yang sudah ada sebelumnya.
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->string('id_vendor')->nullable()->after('tanggal_janji');
            $table->string('vendor')->nullable()->after('id_vendor');
            $table->string('id_material')->nullable()->after('nama_material');
            $table->dropColumn('supplier');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->string('supplier')->nullable();
            $table->dropColumn(['id_vendor', 'vendor', 'id_material']);
        });
    }
};