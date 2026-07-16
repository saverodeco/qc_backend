<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcInspector extends Model
{
    protected $table = 'qc_inspectors';

    protected $fillable = [
        'nama',
        'active_flag',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_flag', 'Y');
    }
}