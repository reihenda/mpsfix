<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Services\RealtimeBalanceService;

class MonthlyCustomerBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'year_month',
        'opening_balance',
        'total_deposits',
        'total_purchases',
        'closing_balance',
        'total_volume_sm3',
        'calculation_details',
        'last_calculated_at'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'total_deposits' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_volume_sm3' => 'decimal:4',
        'calculation_details' => 'array',
        'last_calculated_at' => 'datetime'
    ];

    /**
     * Relationship dengan User (Customer)
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year_month', 'like', $year . '%');
    }

    /**
     * Scope untuk filter berdasarkan customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Get atau create balance untuk customer dan periode tertentu
     */
    public static function getOrCreateForPeriod($customerId, $yearMonth)
    {
        return static::firstOrCreate(
            [
                'customer_id' => $customerId,
                'year_month' => $yearMonth
            ],
            [
                'opening_balance' => 0,
                'total_deposits' => 0,
                'total_purchases' => 0,
                'closing_balance' => 0,
                'total_volume_sm3' => 0,
                'last_calculated_at' => now()
            ]
        );
    }

    /**
     * Update balance dengan data baru
     */
    public function updateBalance($openingBalance, $totalDeposits, $totalPurchases, $totalVolume, $calculationDetails = null)
    {
        $this->update([
            'opening_balance' => $openingBalance,
            'total_deposits' => $totalDeposits,
            'total_purchases' => $totalPurchases,
            'closing_balance' => $openingBalance + $totalDeposits - $totalPurchases,
            'total_volume_sm3' => $totalVolume,
            'calculation_details' => $calculationDetails,
            'last_calculated_at' => now()
        ]);

        // Observer akan otomatis handle sinkronisasi ke users.monthly_balances

        return $this;
    }

    /**
     * Get balance untuk bulan sebelumnya
     */
    public function getPreviousMonthBalance()
    {
        $previousMonth = Carbon::createFromFormat('Y-m', $this->year_month)->subMonth()->format('Y-m');
        
        return static::where('customer_id', $this->customer_id)
            ->where('year_month', $previousMonth)
            ->first();
    }

    /**
     * Get balance untuk bulan berikutnya
     */
    public function getNextMonthBalance()
    {
        $nextMonth = Carbon::createFromFormat('Y-m', $this->year_month)->addMonth()->format('Y-m');
        
        return static::where('customer_id', $this->customer_id)
            ->where('year_month', $nextMonth)
            ->first();
    }


}
