<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receiving_iml', function (Blueprint $table) {
            $table->id();
            $table->string('no_iml')->unique(); // nomor dokumen IML
            $table->string('nama_material');
            $table->string('supplier')->nullable();
            $table->string('no_kendaraan')->nullable();
            $table->decimal('jumlah', 10, 2)->nullable();
            $table->string('satuan')->nullable();
            $table->date('tanggal_kedatangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiving_iml');
    }
};