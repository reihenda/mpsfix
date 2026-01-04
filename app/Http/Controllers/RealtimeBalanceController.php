<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MonthlyCustomerBalance;
use App\Models\TransactionCalculation;
use App\Services\RealtimeBalanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RealtimeBalanceController extends Controller
{
    protected $realtimeBalanceService;

    public function __construct(RealtimeBalanceService $realtimeBalanceService)
    {
        $this->realtimeBalanceService = $realtimeBalanceService;
    }

    /**
     * API endpoint untuk mendapatkan balance customer
     */
    public function getCustomerBalance(Request $request, $customerId)
    {
        $customer = User::find($customerId);
        
        if (!$customer || (!$customer->isCustomer() && !$customer->isFOB())) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $yearMonth = $request->get('year_month', now()->format('Y-m'));
        
        // Get balance dari database
        $balance = $this->realtimeBalanceService->getBalanceForPeriod($customerId, $yearMonth);
        
        if (!$balance) {
            return response()->json([
                'error' => 'Balance data not found',
                'message' => 'Please run balance initialization first'
            ], 404);
        }

        return response()->json([
            'customer_id' => $customerId,
            'customer_name' => $customer->name,
            'year_month' => $yearMonth,
            'balance' => [
                'opening_balance' => $balance->opening_balance,
                'total_deposits' => $balance->total_deposits,
                'total_purchases' => $balance->total_purchases,
                'closing_balance' => $balance->closing_balance,
                'total_volume_sm3' => $balance->total_volume_sm3,
                'last_calculated_at' => $balance->last_calculated_at
            ]
        ]);
    }

    /**
     * API endpoint untuk mendapatkan transaction details
     */
    public function getCustomerTransactions(Request $request, $customerId)
    {
        $customer = User::find($customerId);
        
        if (!$customer || (!$customer->isCustomer() && !$customer->isFOB())) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $yearMonth = $request->get('year_month', now()->format('Y-m'));
        
        // Get transactions dari database
        $transactions = $this->realtimeBalanceService->getTransactionCalculationsForPeriod($customerId, $yearMonth);
        
        return response()->json([
            'customer_id' => $customerId,
            'customer_name' => $customer->name,
            'year_month' => $yearMonth,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'data_pencatatan_id' => $transaction->data_pencatatan_id,
                    'transaction_date' => $transaction->transaction_date,
                    'volume_flow_meter' => $transaction->volume_flow_meter,
                    'koreksi_meter' => $transaction->koreksi_meter,
                    'volume_sm3' => $transaction->volume_sm3,
                    'harga_per_m3' => $transaction->harga_per_m3,
                    'total_harga' => $transaction->total_harga,
                    'pricing_used' => $transaction->pricing_used,
                    'calculated_at' => $transaction->calculated_at
                ];
            }),
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_volume' => $transactions->sum('volume_sm3'),
                'total_amount' => $transactions->sum('total_harga')
            ]
        ]);
    }

    /**
     * Dashboard data untuk analytics/reporting
     */
    public function getDashboardData(Request $request)
    {
        $year = $request->get('year', now()->year);
        
        // Summary untuk semua customer
        $summary = MonthlyCustomerBalance::forYear($year)
            ->selectRaw('
                COUNT(DISTINCT customer_id) as total_customers,
                SUM(total_deposits) as total_deposits,
                SUM(total_purchases) as total_purchases,
                SUM(closing_balance) as total_closing_balance,
                SUM(total_volume_sm3) as total_volume
            ')
            ->first();

        // Monthly trend
        $monthlyTrend = MonthlyCustomerBalance::forYear($year)
            ->selectRaw('
                year_month,
                SUM(total_deposits) as monthly_deposits,
                SUM(total_purchases) as monthly_purchases,
                SUM(closing_balance) as monthly_balance,
                SUM(total_volume_sm3) as monthly_volume
            ')
            ->groupBy('year_month')
            ->orderBy('year_month')
            ->get();

        // Top customers by volume
        $topCustomers = MonthlyCustomerBalance::forYear($year)
            ->join('users', 'monthly_customer_balances.customer_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                SUM(total_volume_sm3) as total_volume,
                SUM(total_purchases) as total_purchases,
                AVG(closing_balance) as avg_balance
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_volume')
            ->take(10)
            ->get();

        return response()->json([
            'year' => $year,
            'summary' => $summary,
            'monthly_trend' => $monthlyTrend,
            'top_customers' => $topCustomers,
            'generated_at' => now()
        ]);
    }

    /**
     * Comparison report: Real-time vs Old calculation
     */
    public function getComparisonReport(Request $request)
    {
        $customers = User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_FOB])
            ->where('use_realtime_calculation', true)
            ->take(10)
            ->get();

        $comparison = [];
        $currentMonth = now()->format('Y-m');

        foreach ($customers as $customer) {
            // Database balance
            $dbBalance = MonthlyCustomerBalance::where('customer_id', $customer->id)
                ->where('year_month', $currentMonth)
                ->first();

            // Old calculation
            $oldBalance = $customer->getCurrentBalance();

            $comparison[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'database_balance' => $dbBalance ? $dbBalance->closing_balance : 0,
                'old_calculation_balance' => $oldBalance,
                'difference' => $dbBalance ? abs($dbBalance->closing_balance - $oldBalance) : abs($oldBalance),
                'match' => $dbBalance ? (abs($dbBalance->closing_balance - $oldBalance) < 0.01) : false
            ];
        }

        return response()->json([
            'comparison' => $comparison,
            'summary' => [
                'total_checked' => count($comparison),
                'matches' => collect($comparison)->where('match', true)->count(),
                'differences_found' => collect($comparison)->where('match', false)->count(),
                'max_difference' => collect($comparison)->max('difference')
            ]
        ]);
    }

    /**
     * Manual trigger untuk update balance customer tertentu
     */
    public function updateCustomerBalance(Request $request, $customerId)
    {
        $customer = User::find($customerId);
        
        if (!$customer || (!$customer->isCustomer() && !$customer->isFOB())) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        $startMonth = $request->get('start_month');
        
        try {
            $result = $this->realtimeBalanceService->updateCustomerBalance($customerId, $startMonth);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer balance updated successfully',
                    'customer_id' => $customerId,
                    'start_month' => $startMonth,
                    'updated_at' => now()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update customer balance'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * System status dan health check
     */
    public function getSystemStatus()
    {
        // Count total data
        $totalBalances = MonthlyCustomerBalance::count();
        $totalTransactions = TransactionCalculation::count();
        $activeCustomers = User::where('use_realtime_calculation', true)->count();
        
        // Recent activity
        $recentUpdates = MonthlyCustomerBalance::orderBy('last_calculated_at', 'desc')
            ->take(10)
            ->with('customer:id,name')
            ->get();

        // Performance metrics
        $start = microtime(true);
        MonthlyCustomerBalance::where('year_month', now()->format('Y-m'))->count();
        $queryTime = (microtime(true) - $start) * 1000;

        return response()->json([
            'status' => 'operational',
            'statistics' => [
                'total_monthly_balances' => $totalBalances,
                'total_transaction_calculations' => $totalTransactions,
                'active_customers' => $activeCustomers,
                'avg_query_time_ms' => number_format($queryTime, 2)
            ],
            'recent_updates' => $recentUpdates->map(function ($balance) {
                return [
                    'customer_name' => $balance->customer->name ?? 'Unknown',
                    'year_month' => $balance->year_month,
                    'last_calculated_at' => $balance->last_calculated_at,
                    'closing_balance' => $balance->closing_balance
                ];
            }),
            'checked_at' => now()
        ]);
    }
}
