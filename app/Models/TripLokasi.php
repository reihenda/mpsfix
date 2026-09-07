<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripLokasi extends Model
{
    use HasFactory;

    protected $table = 'trip_lokasi';

    protected $fillable = [
        'nama_lokasi',
        'status',
        'diajukan_oleh_user_id',
        'approved_by_user_id',
    ];

    public function diajukanOleh()
    {
        return $this->belongsTo(User::class, 'diajukan_oleh_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
