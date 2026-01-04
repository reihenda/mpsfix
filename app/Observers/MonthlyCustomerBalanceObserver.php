<?php

namespace App\Observers;

use App\Models\MonthlyCustomerBalance;
use Illuminate\Support\Facades\Log;

class MonthlyCustomerBalanceObserver
{
    /**
     * Handle the MonthlyCustomerBalance "created" event.
     */
    public function created(MonthlyCustomerBalance $monthlyCustomerBalance)
    {
        $this->syncToUserMonthlyBalances($monthlyCustomerBalance);
    }

    /**
     * Handle the MonthlyCustomerBalance "updated" event.
     */
    public function updated(MonthlyCustomerBalance $monthlyCustomerBalance)
    {
        // Only sync if closing_balance changed
        if ($monthlyCustomerBalance->isDirty('closing_balance')) {
            $this->syncToUserMonthlyBalances($monthlyCustomerBalance);
        }
    }

    /**
     * Handle the MonthlyCustomerBalance "deleted" event.
     */
    public function deleted(MonthlyCustomerBalance $monthlyCustomerBalance)
    {
        $this->syncToUserMonthlyBalances($monthlyCustomerBalance);
    }

    /**
     * Sinkronisasi ke users.monthly_balances
     */
    private function syncToUserMonthlyBalances(MonthlyCustomerBalance $monthlyCustomerBalance)
    {
        try {
            $customer = $monthlyCustomerBalance->customer;
            if (!$customer) {
                return false;
            }

            // Ambil semua monthly balances untuk customer ini (termasuk yang baru saja diupdate)
            $monthlyBalances = MonthlyCustomerBalance::where('customer_id', $monthlyCustomerBalance->customer_id)
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

            Log::info('Observer: User monthly_balances synced', [
                'customer_id' => $monthlyCustomerBalance->customer_id,
                'year_month' => $monthlyCustomerBalance->year_month,
                'closing_balance' => $monthlyCustomerBalance->closing_balance,
                'total_periods' => count($userMonthlyBalances),
                'event' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Observer: Error syncing to user monthly_balances', [
                'customer_id' => $monthlyCustomerBalance->customer_id,
                'year_month' => $monthlyCustomerBalance->year_month,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
