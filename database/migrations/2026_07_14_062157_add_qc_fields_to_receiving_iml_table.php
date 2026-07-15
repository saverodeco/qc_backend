<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom hasil QC ke tabel receiving_iml yang sudah ada.
     * Kolom-kolom ini defaultnya NULL karena diisi belakangan oleh
     * operator QC, bukan saat record IML pertama kali dibuat.
     */
    public function up(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->decimal('mc', 5, 2)->nullable()->after('nama_material');
            $table->decimal('imp', 5, 2)->nullable()->after('mc');
            $table->decimal('ot', 5, 2)->nullable()->after('imp');
            $table->timestamp('qc_at')->nullable()->after('ot');

            // Status alur IML: SCHEDULED, ARRIVED, WAITING_QC, QC_IN_PROGRESS,
            // QC_DONE, UNLOADING, FINISHED
            $table->string('status')->default('SCHEDULED')->after('qc_at');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_iml', function (Blueprint $table) {
            $table->dropColumn(['mc', 'imp', 'ot', 'qc_at', 'status']);
        });
    }
};