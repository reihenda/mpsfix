<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorTrip extends Model
{
    use HasFactory;

    protected $table = 'operator_trip';

    protected $fillable = [
        'operator_gtm_id',
        'user_id',
        'tanggal',
        'nopol',
        'asal_type',
        'asal_id',
        'asal_nama',
        'tujuan_type',
        'tujuan_id',
        'tujuan_nama',
        'tekanan',
        'foto_berangkat_path',
        'latitude_berangkat',
        'longitude_berangkat',
        'waktu_berangkat',
        'foto_sampai_path',
        'latitude_sampai',
        'longitude_sampai',
        'waktu_sampai',
        'status',
        'operator_gtm_lembur_id',
        'sesi_ke',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_berangkat' => 'datetime',
        'waktu_sampai' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lembur()
    {
        return $this->belongsTo(OperatorGtmLembur::class, 'operator_gtm_lembur_id');
    }
}
