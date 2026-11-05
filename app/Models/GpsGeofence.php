<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsGeofence extends Model
{
    protected $table = 'gps_geofence';

    protected $fillable = ['nama', 'tipe', 'customer_id'];

    public const TIPE_CUSTOMER = 'customer';
    public const TIPE_SPBG = 'spbg';
    public const TIPE_LAINNYA = 'lainnya';

    public const TIPE_LABEL = [
        self::TIPE_CUSTOMER => 'Customer / PT (batas trip)',
        self::TIPE_SPBG => 'SPBG (trip lanjut)',
        self::TIPE_LAINNYA => 'Lainnya (trip lanjut)',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Apakah geofence ini menjadi batas awal/akhir trip.
     */
    public function isBatasTrip(): bool
    {
        return $this->tipe === self::TIPE_CUSTOMER;
    }
}
