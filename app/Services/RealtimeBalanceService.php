<?php

namespace App\Services;

use App\Models\User;
use App\Models\DataPencatatan;
use App\Models\MonthlyCustomerBalance;
use App\Models\TransactionCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeBalanceService
{
    /**
     * Update balance real-time untuk customer
     */
    public function updateCustomerBalance($customerId, $startYearMonth = null)
    {
        try {
            DB::beginTransaction();

            $customer = User::find($customerId);
            if (!$customer) {
                throw new \Exception("Customer not found: {$customerId}");
            }

            // Jika tidak ada start month, mulai dari bulan pertama yang ada data
            if (!$startYearMonth) {
                $firstTransaction = $customer->dataPencatatan()
                    ->get()
                    ->filter(function ($item) {
                        $dataInput = is_string($item->data_input) 
                            ? json_decode($item->data_input, true) 
                            : $item->data_input;
                        return !empty($dataInput['pembacaan_awal']['waktu']);
                    })
                    ->sortBy(function ($item) {
                        $dataInput = is_string($item->data_input) 
                            ? json_decode($item->data_input, true) 
                            : $item->data_input;
                        return Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp;
                    })
                    ->first();

                if ($firstTransaction) {
                    $dataInput = is_string($firstTransaction->data_input) 
                        ? json_decode($firstTransaction->data_input, true) 
                        : $firstTransaction->data_input;
                    $startYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                } else {
                    $startYearMonth = now()->format('Y-m');
                }
            }

            // Update semua transaction calculations terlebih dahulu
            $this->updateTransactionCalculations($customerId, $startYearMonth);

            // Update monthly balances
            $this->updateMonthlyBalances($customerId, $startYearMonth);

            // Update timestamp di user
            $customer->update(['balance_last_updated_at' => now()]);

            DB::commit();

            Log::info('Customer balance updated successfully', [
                'customer_id' => $customerId,
                'start_year_month' => $startYearMonth
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating customer balance', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update transaction calculations untuk customer
     */
    private function updateTransactionCalculations($customerId, $startYearMonth)
    {
        $customer = User::find($customerId);
        
        // Get semua data pencatatan dari start month
        $dataPencatatanList = $customer->dataPencatatan()
            ->get()
            ->filter(function ($item) use ($startYearMonth) {
                $dataInput = is_string($item->data_input) 
                    ? json_decode($item->data_input, true) 
                    : $item->data_input;
                
                if (empty($dataInput['pembacaan_awal']['waktu'])) {
                    return false;
                }

                $yearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
                return $yearMonth >= $startYearMonth;
            });

        foreach ($dataPencatatanList as $dataPencatatan) {
            TransactionCalculation::createOrUpdateForDataPencatatan($dataPencatatan);
        }

        Log::info('Transaction calculations updated', [
            'customer_id' => $customerId,
            'transactions_processed' => $dataPencatatanList->count()
        ]);
    }

    /**
     * Update monthly balances untuk customer
     */
    private function updateMonthlyBalances($customerId, $startYearMonth)
    {
        $customer = User::find($customerId);
        
        // Get semua periode yang perlu diupdate
        $periods = $this->getPeriodsToUpdate($customerId, $startYearMonth);

        $previousBalance = 0;

        foreach ($periods as $yearMonth) {
            // Hitung opening balance (closing balance bulan sebelumnya)
            $openingBalance = $previousBalance;

            // Hitung deposits untuk periode ini
            $totalDeposits = $this->calculateDepositsForPeriod($customer, $yearMonth);

            // Hitung purchases dari transaction calculations
            $transactionTotals = TransactionCalculation::getTotalsForCustomerPeriod($customerId, $yearMonth);
            $totalPurchases = $transactionTotals->total_purchases ?? 0;
            $totalVolume = $transactionTotals->total_volume_sm3 ?? 0;

            // Create atau update monthly balance
            $monthlyBalance = MonthlyCustomerBalance::getOrCreateForPeriod($customerId, $yearMonth);
            
            $calculationDetails = [
                'updated_at' => now()->toISOString(),
                'transactions_count' => $transactionTotals->total_transactions ?? 0,
                'deposits_calculated' => true,
                'purchases_calculated' => true
            ];

            $monthlyBalance->updateBalance(
                $openingBalance,
                $totalDeposits,
                $totalPurchases,
                $totalVolume,
                $calculationDetails
            );

            // Set previous balance untuk periode berikutnya
            $previousBalance = $monthlyBalance->closing_balance;

            Log::debug('Monthly balance updated', [
                'customer_id' => $customerId,
                'year_month' => $yearMonth,
                'opening_balance' => $openingBalance,
                'total_deposits' => $totalDeposits,
                'total_purchases' => $totalPurchases,
                'closing_balance' => $previousBalance
            ]);
        }

        // Sinkronisasi ke monthly_balances di tabel users
        $this->syncToUserMonthlyBalances($customer);
    }

    /**
     * Get periods yang perlu diupdate
     */
    private function getPeriodsToUpdate($customerId, $startYearMonth)
    {
        // Get periode dari start month sampai sekarang
        $startDate = Carbon::createFromFormat('Y-m', $startYearMonth);
        $endDate = Carbon::now();
        
        $periods = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $periods[] = $currentDate->format('Y-m');
            $currentDate->addMonth();
        }

        return $periods;
    }

    /**
     * Calculate deposits untuk periode tertentu
     */
    private function calculateDepositsForPeriod($customer, $yearMonth)
    {
        $depositHistory = is_array($customer->deposit_history) 
            ? $customer->deposit_history 
            : json_decode($customer->deposit_history ?? '[]', true);

        $totalDeposits = 0;

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') === $yearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';
                    
                    if ($keterangan === 'pengurangan') {
                        $totalDeposits -= abs($amount);
                    } else {
                        $totalDeposits += $amount;
                    }
                }
            }
        }

        return $totalDeposits;
    }

    /**
     * Trigger update ketika ada perubahan data pencatatan
     */
    public function onDataPencatatanChanged(DataPencatatan $dataPencatatan, $action = 'updated')
    {
        if (!$dataPencatatan->customer_id) {
            return false;
        }

        // Update transaction calculation untuk data ini
        if ($action !== 'deleted') {
            TransactionCalculation::createOrUpdateForDataPencatatan($dataPencatatan);
        } else {
            // Hapus transaction calculation jika data dihapus
            TransactionCalculation::where('data_pencatatan_id', $dataPencatatan->id)->delete();
        }

        // Get year month dari data
        $dataInput = is_string($dataPencatatan->data_input) 
            ? json_decode($dataPencatatan->data_input, true) 
            : $dataPencatatan->data_input;

        $startYearMonth = null;
        if (!empty($dataInput['pembacaan_awal']['waktu'])) {
            $startYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
        }

        // Update balance mulai dari periode tersebut
        return $this->updateCustomerBalance($dataPencatatan->customer_id, $startYearMonth);
    }

    /**
     * Trigger update ketika ada perubahan deposit
     */
    public function onDepositChanged($customerId, $depositDate = null)
    {
        $startYearMonth = null;
        if ($depositDate) {
            $startYearMonth = Carbon::parse($depositDate)->format('Y-m');
        }

        return $this->updateCustomerBalance($customerId, $startYearMonth);
    }

    /**
     * Trigger update ketika ada perubahan pricing
     */
    public function onPricingChanged($customerId, $effectiveDate = null)
    {
        $startYearMonth = null;
        if ($effectiveDate) {
            $startYearMonth = Carbon::parse($effectiveDate)->format('Y-m');
        }

        // Recalculate semua transaction calculations dari effective date
        TransactionCalculation::recalculateForCustomer($customerId, $startYearMonth);

        return $this->updateCustomerBalance($customerId, $startYearMonth);
    }

    /**
     * Get balance data untuk customer dan periode tertentu
     */
    public function getBalanceForPeriod($customerId, $yearMonth)
    {
        return MonthlyCustomerBalance::where('customer_id', $customerId)
            ->where('year_month', $yearMonth)
            ->first();
    }

    /**
     * Get transaction calculations untuk customer dan periode tertentu
     */
    public function getTransactionCalculationsForPeriod($customerId, $yearMonth)
    {
        return TransactionCalculation::where('customer_id', $customerId)
            ->where('year_month', $yearMonth)
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Initialize real-time system untuk customer existing
     */
    public function initializeCustomer($customerId)
    {
        $customer = User::find($customerId);
        if (!$customer) {
            return false;
        }

        // Set flag untuk menggunakan real-time calculation
        $customer->update(['use_realtime_calculation' => true]);

        // Update semua data
        return $this->updateCustomerBalance($customerId);
    }

    /**
     * Sinkronisasi closing_balance dari monthly_customer_balances ke monthly_balances di users
     */
    private function syncToUserMonthlyBalances($customer)
    {
        try {
            // Ambil semua monthly balances untuk customer ini
            $monthlyBalances = MonthlyCustomerBalance::where('customer_id', $customer->id)
                ->orderBy('year_month')
                ->get();

            // Siapkan array untuk disimpan di users.monthly_balances
            $userMonthlyBalances = [];

            foreach ($monthlyBalances as $balance) {
                $userMonthlyBalances[$balance->year_month] = floatval($balance->closing_balance);
            }

            // Update monthly_balances di tabel users
            $customer->update([
                'monthly_balances' => $userMonthlyBalances
            ]);

            Log::info('User monthly_balances synced', [
                'customer_id' => $customer->id,
                'periods_synced' => count($userMonthlyBalances),
                'monthly_balances' => $userMonthlyBalances
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error syncing to user monthly_balances', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
