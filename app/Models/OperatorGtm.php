<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorGtm extends Model
{
    use HasFactory;

    protected $table = 'operator_gtm';
    
    protected $fillable = [
        'nama',
        'lokasi_kerja',
        'gaji_pokok',
        'jam_kerja',
        'tanggal_bergabung',
    ];

    /**
     * Mendapatkan semua data lembur untuk operator ini
     */
    public function lemburRecords()
    {
        return $this->hasMany(OperatorGtmLembur::class, 'operator_gtm_id');
    }
}