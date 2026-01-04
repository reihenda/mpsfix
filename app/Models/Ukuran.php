<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ukuran extends Model
{
    use HasFactory;

    protected $table = 'ukuran';

    protected $fillable = [
        'nama_ukuran',
    ];

    /**
     * Get nomor polisi records associated with this ukuran.
     */
    public function nomorPolisi()
    {
        return $this->hasMany(NomorPolisi::class);
    }
}