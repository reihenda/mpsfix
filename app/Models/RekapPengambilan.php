<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapPengambilan extends Model
{
    use HasFactory;

    protected $table = 'rekap_pengambilan';

    protected $fillable = [
        'customer_id',
        'tanggal',
        'nopol',
        'volume',
        'alamat_pengambilan_id',
        'alamat_pengambilan',
        'keterangan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'datetime',
        'volume' => 'float',
    ];

    /**
     * Get the customer associated with the rekap pengambilan.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the alamat pengambilan that owns the rekap pengambilan.
     */
    public function alamatPengambilan()
    {
        return $this->belongsTo(AlamatPengambilan::class);
    }

    /**
     * Get the data pencatatan associated with this rekap pengambilan.
     */
    public function dataPencatatan()
    {
        return $this->hasOne(DataPencatatan::class, 'rekap_pengambilan_id');
    }

    /**
     * Filter pengambilan by month and year
     */
    public static function filterByMonthYear($month, $year)
    {
        return self::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->orderBy('tanggal', 'desc');
    }

    /**
     * Get total volume for a given month and year
     */
    public static function getTotalVolumeMonthly($month, $year)
    {
        return self::filterByMonthYear($month, $year)->sum('volume');
    }

    /**
     * Get total volume for a specific date
     */
    public static function getTotalVolumeDaily($date)
    {
        return self::whereDate('tanggal', $date)->sum('volume');
    }
}
