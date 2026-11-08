<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPerjalananRute extends Model
{
    protected $table = 'jadwal_perjalanan_rute';

    protected $fillable = ['jadwal_perjalanan_id', 'urutan', 'tujuan', 'catatan', 'tercapai_pada'];

    protected $casts = ['tercapai_pada' => 'datetime'];

    public function jadwal()
    {
        return $this->belongsTo(JadwalPerjalanan::class, 'jadwal_perjalanan_id');
    }
}
