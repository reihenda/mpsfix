<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class KasTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_number',
        'account_id',
        'transaction_date',
        'description',
        'credit',
        'debit',
        'balance',
        'year',
        'month'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'transaction_date' => 'date',
        'credit' => 'decimal:2',
        'debit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the account that owns the transaction.
     */
    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    /**
     * Set the transaction date and automatically set year and month.
     */
    public function setTransactionDateAttribute($value)
    {
        $this->attributes['transaction_date'] = $value;
        $date = Carbon::parse($value);
        $this->attributes['year'] = $date->year;
        $this->attributes['month'] = $date->month;
    }

    /**
     * Generate a unique voucher number for new transactions.
     * Format: KAS0001, KAS0002, etc. Resets every year.
     */
    public static function generateVoucherNumber($year = null)
    {
        $year = $year ?: Carbon::now()->year;
        
        // Get the highest voucher number for this year
        $lastVoucher = self::where('year', $year)
            ->where('voucher_number', 'LIKE', 'KAS%')
            ->orderByRaw('CAST(SUBSTRING(voucher_number, 4) AS UNSIGNED) DESC')
            ->first();
        
        if (!$lastVoucher) {
            // No voucher exists for this year yet
            return 'KAS0001';
        }
        
        // Extract the numeric part of the last voucher number
        $lastNumber = (int) substr($lastVoucher->voucher_number, 3);
        $newNumber = $lastNumber + 1;
        
        // Format the new number with leading zeros
        return 'KAS' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope a query to only include transactions for a specific month and year.
     */
    public function scopeForMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)
                     ->where('year', $year);
    }
}
