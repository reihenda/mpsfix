<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorTripPerbaikan extends Model
{
    use HasFactory;

    protected $table = 'operator_trip_perbaikan';

    protected $fillable = [
        'operator_gtm_id',
        'user_id',
        'tanggal',
        'nopol',
        'asal_nama',
        'tujuan_nama',
        'tekanan',
        'foto_bukti_path',
        'waktu_klaim',
        'keterangan',
        'status',
        'reviewed_by_user_id',
        'catatan_admin',
        'sesi_ke',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_klaim' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
