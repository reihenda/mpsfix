<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'invoice_id',
        'billing_number',
        'billing_date',
        'total_volume',
        'total_amount',
        'total_deposit',
        'previous_balance',
        'current_balance',
        'amount_to_pay',
        'period_month',
        'period_year',
        'period_type',
        'custom_start_date',
        'custom_end_date',
    ];

    // Relasi dengan User (Customer)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Relasi dengan Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // Mendapatkan format nomor billing (sama dengan invoice)
    public static function generateBillingNumber($customer, $date = null, $periodType = 'monthly', $customStartDate = null)
    {
        // Untuk periode custom, gunakan custom start date
        if ($periodType === 'custom' && $customStartDate) {
            $date = \Carbon\Carbon::parse($customStartDate);
        } else {
            $date = $date ? \Carbon\Carbon::parse($date) : now();
        }
        
        // Format billing number sama dengan invoice (hanya prefix yang berbeda)
        $customerCode = substr(strtoupper($customer->name), 0, 4);
        
        // Dapatkan nomor urut gabungan untuk customer ini (billing + invoice)
        $billingCount = self::where('customer_id', $customer->id)
                           ->whereMonth('created_at', $date->month)
                           ->whereYear('created_at', $date->year)
                           ->count();
                           
        $invoiceCount = \App\Models\Invoice::where('customer_id', $customer->id)
                                           ->whereMonth('created_at', $date->month)
                                           ->whereYear('created_at', $date->year)
                                           ->count();
        
        $count = $billingCount + $invoiceCount + 1;
        
        $billingNumber = sprintf('%03d/MPS/BIL-%s/%s/%s', 
            $count,
            $customerCode,
            $date->format('m'),
            $date->format('Y')
        );
        
        return $billingNumber;
    }

    // Generate nomor yang sama dengan pasangan invoice
    public static function generateSyncedNumber($customer, $invoiceNumber)
    {
        return str_replace('/INV-', '/BIL-', $invoiceNumber);
    }
}
