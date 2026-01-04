<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProformaInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'proforma_number',
        'proforma_date',
        'due_date',
        'total_amount',
        'total_volume',
        'volume_per_day',
        'price_per_sm3',
        'total_days',
        'status',
        'description',
        'no_kontrak',
        'id_pelanggan',
        'period_start_date',
        'period_end_date',
        'validity_date',
    ];

    protected $casts = [
        'proforma_date' => 'date',
        'due_date' => 'date',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'validity_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_volume' => 'decimal:3',
    ];

    // Relasi dengan User (Customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Generate nomor proforma invoice
    public static function generateProformaNumber($customer, $date = null)
    {
        $date = $date ? Carbon::parse($date) : now();
        
        // Format: 'no'/MPS/PI-'CUSTOMER'/'tgl'/'thn'
        // Misalnya: 001/MPS/PI-NOMI/12/2024
        $customerCode = substr(strtoupper($customer->name), 0, 4);
        
        // Dapatkan nomor urut proforma invoice untuk customer ini dalam bulan yang sama
        $count = self::where('customer_id', $customer->id)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count() + 1;
        
        $proformaNumber = sprintf('%03d/MPS/PI-%s/%s/%s', 
            $count,
            $customerCode,
            $date->format('m'),
            $date->format('Y')
        );
        
        return $proformaNumber;
    }

    // Scope untuk filter berdasarkan status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk filter berdasarkan customer
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Accessor untuk format tanggal periode
    public function getPeriodFormattedAttribute()
    {
        $start = $this->period_start_date->format('d/m/Y');
        $end = $this->period_end_date->format('d/m/Y');
        
        if ($start === $end) {
            return $start;
        }
        
        return "{$start} - {$end}";
    }

    // Check if proforma is expired
    public function getIsExpiredAttribute()
    {
        if (!$this->validity_date) {
            return false;
        }
        
        return $this->validity_date->isPast();
    }

    // Get days until expiry
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->validity_date) {
            return null;
        }
        
        return now()->diffInDays($this->validity_date, false);
    }

    // Calculate total volume based on volume per day and total days
    public function calculateTotalVolume()
    {
        return $this->volume_per_day * $this->total_days;
    }

    // Calculate total amount based on total volume and price per sm3
    public function calculateTotalAmount()
    {
        return $this->calculateTotalVolume() * $this->price_per_sm3;
    }

    // Calculate total days from period dates
    public function calculateTotalDays()
    {
        if (!$this->period_start_date || !$this->period_end_date) {
            return 0;
        }
        
        return $this->period_start_date->diffInDays($this->period_end_date); // Tanpa +1, jadi exclusive
    }
}
