<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaGagas extends Model
{
    use HasFactory;

    protected $table = 'harga_gagas';

    protected $fillable = [
        'harga_usd',
        'rate_konversi_idr',
        'kalori',
        'periode_tahun',
        'periode_bulan',
    ];

    /**
     * Method untuk upsert data harga gagas
     * Update jika periode sudah ada, create jika belum
     */
    public static function upsertHargaGagas($data)
    {
        return static::updateOrCreate(
            [
                'periode_tahun' => $data['periode_tahun'],
                'periode_bulan' => $data['periode_bulan']
            ],
            [
                'harga_usd' => $data['harga_usd'],
                'rate_konversi_idr' => $data['rate_konversi_idr'],
                'kalori' => $data['kalori'],
            ]
        );
    }

    protected $casts = [
        'harga_usd' => 'decimal:2',
        'rate_konversi_idr' => 'decimal:2',
        'kalori' => 'decimal:2',
        'periode_tahun' => 'integer',
        'periode_bulan' => 'integer',
    ];

    /**
     * Scope untuk periode tertentu
     */
    public function scopePeriode($query, $tahun, $bulan = null)
    {
        $query = $query->where('periode_tahun', $tahun);
        
        if ($bulan) {
            $query = $query->where('periode_bulan', $bulan);
        }
        
        return $query;
    }

    /**
     * Mendapatkan harga dalam IDR
     */
    public function getHargaIdrAttribute()
    {
        return $this->harga_usd * $this->rate_konversi_idr;
    }

    /**
     * Format tampilan periode
     */
    public function getPeriodeFormatAttribute()
    {
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return $bulanNames[$this->periode_bulan] . ' ' . $this->periode_tahun;
    }
}
