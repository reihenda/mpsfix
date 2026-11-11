<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Periode lembur yang sudah pernah ditarik penuh dari Cartrack.
 */
class GpsSinkronPeriode extends Model
{
    protected $table = 'gps_sinkron_periode';

    protected $fillable = ['periode', 'awal', 'akhir', 'terakhir_penuh_pada'];

    protected $casts = [
        'awal' => 'datetime',
        'akhir' => 'datetime',
        'terakhir_penuh_pada' => 'datetime',
    ];
}
