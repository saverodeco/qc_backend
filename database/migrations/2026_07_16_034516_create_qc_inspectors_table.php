<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_inspectors', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 35); // sama panjangnya dengan receiving_iml.qc_inspector
            $table->char('active_flag', 1)->default('Y');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspectors');
    }
};