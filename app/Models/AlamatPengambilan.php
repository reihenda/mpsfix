<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlamatPengambilan extends Model
{
    use HasFactory;

    protected $table = 'alamat_pengambilan';

    protected $fillable = [
        'nama_alamat',
    ];

    /**
     * Get rekap pengambilan records associated with this alamat.
     */
    public function rekapPengambilan()
    {
        return $this->hasMany(RekapPengambilan::class);
    }
}
