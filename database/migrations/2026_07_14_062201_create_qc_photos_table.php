<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_photos', function (Blueprint $table) {
            $table->id();

            // FK ke receiving_iml.no_iml (string, karena IML dipakai sebagai
            // kunci pencarian utama di seluruh alur QC)
            $table->string('no_iml');
            $table->foreign('no_iml')
                ->references('no_iml')
                ->on('receiving_iml')
                ->onDelete('cascade');

            $table->string('photo_path'); // relative: qc_photos/{no_iml}/xxx.jpg
            $table->unsignedInteger('urutan_foto')->default(1);
            $table->timestamps();

            $table->index('no_iml');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_photos');
    }
};