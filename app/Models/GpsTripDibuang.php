<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jejak trip noise yang sudah dihapus (kunci: nopol + waktu mulai) agar tidak muncul lagi saat upload ulang.
 */
class GpsTripDibuang extends Model
{
    protected $table = 'gps_trip_dibuang';

    protected $fillable = ['nopol', 'waktu_mulai'];
}
