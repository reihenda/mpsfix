<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorGtmLembur extends Model
{
    use HasFactory;

    protected $table = 'operator_gtm_lembur';
    
    protected $fillable = [
        'operator_gtm_id',
        'tanggal',
        'jam_masuk_sesi_1',
        'jam_keluar_sesi_1',
        'jam_masuk_sesi_2',
        'jam_keluar_sesi_2',
        'jam_masuk_sesi_3',
        'jam_keluar_sesi_3',
        'jam_masuk_sesi_4',
        'jam_keluar_sesi_4',
        'jam_masuk_sesi_5',
        'jam_keluar_sesi_5',
        'total_jam_kerja',
        'total_jam_lembur',
        'upah_lembur',
    ];

    /**
     * Mendapatkan operator yang terkait dengan record lembur ini
     */
    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }
}