<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class TransactionCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'data_pencatatan_id',
        'year_month',
        'transaction_date',
        'volume_flow_meter',
        'koreksi_meter',
        'volume_sm3',
        'harga_per_m3',
        'total_harga',
        'pricing_used',
        'tekanan_keluar',
        'suhu',
        'calculated_at',
        'is_recalculated'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'volume_flow_meter' => 'decimal:4',
        'koreksi_meter' => 'decimal:8',
        'volume_sm3' => 'decimal:4',
        'harga_per_m3' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'pricing_used' => 'array',
        'tekanan_keluar' => 'decimal:3',
        'suhu' => 'decimal:2',
        'calculated_at' => 'datetime',
        'is_recalculated' => 'boolean'
    ];

    /**
     * Relationship dengan User (Customer)
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relationship dengan DataPencatatan
     */
    public function dataPencatatan()
    {
        return $this->belongsTo(DataPencatatan::class, 'data_pencatatan_id');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $yearMonth)
    {
        return $query->where('year_month', $yearMonth);
    }

    /**
     * Scope untuk filter berdasarkan customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope untuk filter berdasarkan rentang tanggal
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Create atau update calculation untuk data pencatatan
     */
    public static function createOrUpdateForDataPencatatan(DataPencatatan $dataPencatatan)
    {
        $customer = $dataPencatatan->customer;
        if (!$customer) {
            return null;
        }

        // Parse data input
        $dataInput = is_string($dataPencatatan->data_input) 
            ? json_decode($dataPencatatan->data_input, true) 
            : $dataPencatatan->data_input;

        if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
            return null;
        }

        $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
        $yearMonth = $waktuAwal->format('Y-m');
        $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

        // Get pricing untuk periode ini
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth, $waktuAwal);
        
        $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
        $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
        $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
        $totalHarga = $volumeSm3 * $hargaPerM3;

        // Create atau update calculation
        return static::updateOrCreate(
            [
                'data_pencatatan_id' => $dataPencatatan->id
            ],
            [
                'customer_id' => $customer->id,
                'year_month' => $yearMonth,
                'transaction_date' => $waktuAwal->toDateString(),
                'volume_flow_meter' => $volumeFlowMeter,
                'koreksi_meter' => $koreksiMeter,
                'volume_sm3' => $volumeSm3,
                'harga_per_m3' => $hargaPerM3,
                'total_harga' => $totalHarga,
                'pricing_used' => $pricingInfo,
                'tekanan_keluar' => floatval($pricingInfo['tekanan_keluar'] ?? $customer->tekanan_keluar),
                'suhu' => floatval($pricingInfo['suhu'] ?? $customer->suhu),
                'calculated_at' => now(),
                'is_recalculated' => static::where('data_pencatatan_id', $dataPencatatan->id)->exists()
            ]
        );
    }

    /**
     * Get total untuk customer dalam periode tertentu
     */
    public static function getTotalsForCustomerPeriod($customerId, $yearMonth)
    {
        return static::where('customer_id', $customerId)
            ->where('year_month', $yearMonth)
            ->selectRaw('
                SUM(volume_sm3) as total_volume_sm3,
                SUM(total_harga) as total_purchases,
                COUNT(*) as total_transactions
            ')
            ->first();
    }

    /**
     * Recalculate semua calculations untuk customer
     */
    public static function recalculateForCustomer($customerId, $startYearMonth = null)
    {
        $customer = User::find($customerId);
        if (!$customer) {
            return false;
        }

        $query = static::where('customer_id', $customerId);
        
        if ($startYearMonth) {
            $query->where('year_month', '>=', $startYearMonth);
        }

        $calculations = $query->get();
        $updated = 0;

        foreach ($calculations as $calculation) {
            $dataPencatatan = $calculation->dataPencatatan;
            if ($dataPencatatan) {
                static::createOrUpdateForDataPencatatan($dataPencatatan);
                $updated++;
            }
        }

        return $updated;
    }
}
