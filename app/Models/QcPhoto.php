<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_iml',
        'photo_path',
        'urutan_foto',
    ];

    public function receivingIml(): BelongsTo
    {
        return $this->belongsTo(ReceivingIml::class, 'no_iml', 'no_iml');
    }

    // URL publik foto (local storage). Pakai asset() supaya tidak
    // bergantung pada disk default di .env.
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->photo_path);
    }

    protected $appends = ['url'];
}