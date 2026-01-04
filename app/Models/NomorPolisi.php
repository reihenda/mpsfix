<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomorPolisi extends Model
{
    use HasFactory;

    protected $table = 'nomor_polisi';

    protected $fillable = [
        'nopol',
        'keterangan',
        'jenis',
        'ukuran_id',
        'area_operasi',
        'no_gtm',
        'status',
        'iso',
        'coi',
    ];

    /**
     * Get rekap pengambilan records associated with this nomor polisi.
     */
    public function rekapPengambilan()
    {
        return $this->hasMany(RekapPengambilan::class, 'nopol', 'nopol');
    }

    /**
     * Get the ukuran that owns the nomor polisi.
     */
    public function ukuran()
    {
        return $this->belongsTo(Ukuran::class);
    }
}
