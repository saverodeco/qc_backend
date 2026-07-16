<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rombak total supaya sejajar dengan skema perusahaan
     * [dbo].[IMLQCInspection Header] (SQL Server), diterjemahkan ke
     * konvensi snake_case Postgres. Beberapa penyesuaian sengaja dibuat
     * — lihat komentar di tiap bagian.
     */
    public function up(): void
    {
        // qc_photos masih punya FK ke receiving_iml — lepas dulu, atau
        // Postgres akan menolak drop table di bawah ini.
        Schema::table('qc_photos', function (Blueprint $table) {
            $table->dropForeign(['no_iml']);
        });

        Schema::dropIfExists('receiving_iml');

        Schema::create('receiving_iml', function (Blueprint $table) {
            // Tetap pakai id auto-increment sebagai primary key Eloquent
            // (pola yang sudah ada), bukan composite key. Kunci natural
            // perusahaan (id_mill + no_iml) jadi unique constraint di bawah.
            $table->id();

            // --- Field yang ADA di skema perusahaan ---
            $table->string('id_mill', 10);
            $table->integer('no_iml'); // NoIML: INT di perusahaan, sebelumnya varchar di sini
            $table->integer('no_timbang')->nullable();
            $table->integer('iml_seq');
            $table->integer('iml_deliv_seq');
            $table->integer('qc_no');
            $table->string('txt_qc_no', 10)->nullable();

            $table->string('id_supplier', 10)->nullable(); // dulu id_vendor
            $table->string('id_jenis_kendaraan', 10)->nullable();
            $table->string('no_polisi', 10)->nullable(); // dulu no_kendaraan
            $table->string('id_jenis_kertas', 10)->nullable(); // dulu id_material

            $table->decimal('netto_timbang', 18, 2)->default(0);
            $table->decimal('mc', 18, 2)->default(0); // dulu average_mc
            $table->decimal('ot', 18, 2)->default(0);
            $table->decimal('im', 18, 2)->default(0); // dulu imp — nama disamakan ke perusahaan
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('net_weight', 18, 2)->default(0);
            $table->decimal('harga', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);

            $table->char('flag_print', 1)->nullable();
            $table->integer('re_print')->nullable();
            $table->string('id_kontraktor', 10)->nullable();
            $table->string('id_remark', 10)->nullable();

            // Cuma QCInspector yang diadopsi — RMPPInspector & SPInspector
            // sengaja tidak ikut (keputusan: operator lain tidak dicatat).
            $table->string('qc_inspector', 35)->nullable();

            $table->string('id_manual_type', 10)->nullable();
            $table->string('id_lokasi_bongkar', 10)->nullable();
            $table->string('remark', 100)->nullable();

            $table->char('active_flag', 1)->default('Y');
            $table->string('crt_usr_id', 20)->default('system');
            $table->timestamp('ts_crt')->useCurrent();
            $table->string('mod_usr_id', 20)->default('system');
            $table->timestamp('ts_mod')->useCurrent();

            // --- Field TAMBAHAN di luar skema perusahaan ---
            // Perlu buat alur kerja app QC mobile ini; tidak ada di
            // IMLQCInspection Header (atau mungkin ada di tabel/logic lain
            // milik perusahaan yang tidak kelihatan dari definisi ini).
            $table->date('tanggal_janji');
            $table->date('tanggal_kedatangan')->nullable();
            $table->string('status', 20)->default('WAITING_QC'); // WAITING_QC | QC_IN_PROGRESS | QC_DONE
            $table->integer('jumlah')->nullable();
            $table->string('satuan', 20)->nullable();

            // Nama vendor/material buat ditampilkan langsung tanpa join —
            // perusahaan kemungkinan resolve ini dari tabel master Supplier/
            // JenisKertas terpisah yang tidak kita punya di sini.
            $table->string('vendor', 100)->nullable();
            $table->string('nama_material', 100)->nullable();

            // Dipakai buat dicari lewat lookup (dulu unique sendiri) —
            // sekarang unique digabung dengan id_mill sesuai kunci natural
            // perusahaan. Unique tunggal di no_iml tetap dipertahankan
            // supaya qc_photos masih bisa FK langsung ke kolom ini (aman
            // selama aplikasi ini cuma dipakai untuk satu id_mill).
            $table->unique(['id_mill', 'no_iml']);
            $table->unique('no_iml');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiving_iml');
    }
};