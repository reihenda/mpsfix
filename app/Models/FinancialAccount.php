<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'description',
        'account_type',
        'is_active'
    ];

    /**
     * Get all transactions related to this account for Kas
     */
    public function kasTransactions()
    {
        return $this->hasMany(KasTransaction::class, 'account_id');
    }

    /**
     * Get all transactions related to this account for Bank
     */
    public function bankTransactions()
    {
        return $this->hasMany(BankTransaction::class, 'account_id');
    }
    
    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope a query to only include accounts of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }
}
