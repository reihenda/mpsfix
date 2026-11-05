<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsTripImport extends Model
{
    protected $table = 'gps_trip_import';

    protected $fillable = [
        'nama_file',
        'periode_mulai',
        'periode_selesai',
        'jumlah_kendaraan',
        'jumlah_trip',
        'user_id',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
