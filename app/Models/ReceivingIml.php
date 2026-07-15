<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivingIml extends Model
{
    use HasFactory;

    protected $table = 'receiving_iml';

    // Karena no_iml dipakai sebagai kunci pencarian, kita pakai id auto-increment
    // sebagai primary key asli tapi no_iml wajib unique (diatur di migration awal
    // pembuatan tabel ini, di luar migration QC).
    protected $fillable = [
        'no_iml',
        'tanggal_janji',
        'id_vendor',
        'vendor',
        'id_material',
        'nama_material',
        'no_kendaraan',
        'jumlah',
        'satuan',
        'tanggal_kedatangan',
        'average_mc',
        'imp',
        'ot',
        'qc_at',
        'status',
    ];

    protected $casts = [
        'tanggal_janji' => 'date',
        'tanggal_kedatangan' => 'date',
        'qc_at' => 'datetime',
        'average_mc' => 'decimal:2',
        'imp' => 'decimal:2',
        'ot' => 'decimal:2',
    ];

    // Batas maksimal foto QC per IML (sesuai desain UI: maks 20 foto)
    public const MAX_PHOTOS = 20;

    public function photos(): HasMany
    {
        return $this->hasMany(QcPhoto::class, 'no_iml', 'no_iml')
            ->orderBy('urutan_foto');
    }

    public function isReadyForQc(): bool
    {
        return $this->status === 'WAITING_QC';
    }

    public function isQcDone(): bool
    {
        return $this->status === 'QC_DONE';
    }
}