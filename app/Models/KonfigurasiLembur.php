<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KonfigurasiLembur extends Model
{
    use HasFactory;

    protected $table = 'konfigurasi_lembur';
    
    protected $fillable = [
        'nama_konfigurasi',
        'tarif_per_jam',
        'is_active',
    ];

    /**
     * Mendapatkan tarif lembur yang aktif
     */
    public static function getTarifLembur()
    {
        $konfigurasi = self::where('is_active', true)->first();
        return $konfigurasi ? $konfigurasi->tarif_per_jam : 20000; // Default 20000 jika tidak ada konfigurasi
    }
}