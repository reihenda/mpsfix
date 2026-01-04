<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataPencatatan extends Model
{
    use HasFactory;

    protected $table = 'data_pencatatan';

    protected $fillable = [
        'customer_id',
        'nama_customer',
        'data_input',
        'harga_final',
        'status_pembayaran'

    ];

    // Relasi dengan User (Customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Method perhitungan harga
    public function hitungHarga()
    {
        // Logika perhitungan harga custom
        $data = json_decode($this->data_input, true);

        $total = 0;
        // Contoh perhitungan sederhana
        if (isset($data['volume'])) {
            $total += $data['volume'] * 1000;
        }

        if (isset($data['kompleksitas'])) {
            $total += $data['kompleksitas'] * 500;
        }

        $this->harga_final = $total;
        $this->save();

        return $total;
    }
}
