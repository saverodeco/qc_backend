<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivingIml extends Model
{
    use HasFactory;

    protected $table = 'receiving_iml';

    // ts_crt/ts_mod di skema perusahaan menggantikan peran created_at/
    // updated_at bawaan Laravel — matikan timestamps otomatis supaya tidak
    // dobel, dan kita isi ts_crt/ts_mod manual (lihat boot() di bawah).
    public $timestamps = false;

    protected $fillable = [
        'id_mill',
        'no_iml',
        'no_timbang',
        'iml_seq',
        'iml_deliv_seq',
        'qc_no',
        'txt_qc_no',
        'id_supplier',
        'vendor',
        'id_jenis_kendaraan',
        'no_polisi',
        'id_jenis_kertas',
        'nama_material',
        'netto_timbang',
        'mc',
        'ot',
        'im',
        'discount',
        'net_weight',
        'harga',
        'total',
        'flag_print',
        're_print',
        'id_kontraktor',
        'id_remark',
        'qc_inspector',
        'id_manual_type',
        'id_lokasi_bongkar',
        'remark',
        'active_flag',
        'crt_usr_id',
        'ts_crt',
        'mod_usr_id',
        'ts_mod',
        // Tambahan di luar skema perusahaan, khusus alur QC mobile:
        'tanggal_janji',
        'tanggal_kedatangan',
        'status',
        'jumlah',
        'satuan',
    ];

    protected $casts = [
        'tanggal_janji' => 'date',
        'tanggal_kedatangan' => 'date',
        'ts_crt' => 'datetime',
        'ts_mod' => 'datetime',
        'netto_timbang' => 'decimal:2',
        'mc' => 'decimal:2',
        'ot' => 'decimal:2',
        'im' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_weight' => 'decimal:2',
        'harga' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Batas maksimal foto QC per IML (sesuai desain UI: maks 20 foto)
    public const MAX_PHOTOS = 20;

    protected static function booted(): void
    {
        static::creating(function (ReceivingIml $iml) {
            $iml->ts_crt ??= now();
            $iml->ts_mod = now();
        });

        static::updating(function (ReceivingIml $iml) {
            $iml->ts_mod = now();
        });
    }

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