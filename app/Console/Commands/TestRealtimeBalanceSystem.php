<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MonthlyCustomerBalance;
use App\Models\TransactionCalculation;
use App\Services\RealtimeBalanceService;

class TestRealtimeBalanceSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:test-system 
                            {--customer_id= : Test specific customer by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test real-time balance system functionality';

    protected $realtimeBalanceService;

    public function __construct(RealtimeBalanceService $realtimeBalanceService)
    {
        parent::__construct();
        $this->realtimeBalanceService = $realtimeBalanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->option('customer_id');

        if ($customerId) {
            $customer = User::find($customerId);
            if (!$customer) {
                $this->error("Customer with ID {$customerId} not found");
                return 1;
            }
            $this->testCustomer($customer);
        } else {
            // Test dengan customer pertama yang ada
            $customer = User::where('role', User::ROLE_CUSTOMER)
                ->where('use_realtime_calculation', true)
                ->first();

            if (!$customer) {
                $this->error('No customer found with real-time calculation enabled');
                $this->info('Run: php artisan balance:initialize-realtime --all');
                return 1;
            }

            $this->testCustomer($customer);
        }

        return 0;
    }

    private function testCustomer(User $customer)
    {
        $this->info("🧪 Testing Real-time Balance System");
        $this->info("Customer: {$customer->name} (ID: {$customer->id})");
        $this->newLine();

        // Test 1: Check system status
        $this->info("1️⃣ System Status Check");
        $this->checkSystemStatus($customer);
        $this->newLine();

        // Test 2: Check data availability
        $this->info("2️⃣ Data Availability Check");
        $this->checkDataAvailability($customer);
        $this->newLine();

        // Test 3: Compare calculations
        $this->info("3️⃣ Calculation Accuracy Test");
        $this->compareCalculations($customer);
        $this->newLine();

        // Test 4: Performance test
        $this->info("4️⃣ Performance Test");
        $this->performanceTest($customer);
        $this->newLine();

        $this->info("✅ Test completed!");
    }

    private function checkSystemStatus(User $customer)
    {
        // Check if customer has real-time enabled
        if ($customer->use_realtime_calculation) {
            $this->info("✅ Real-time calculation: ENABLED");
        } else {
            $this->warn("⚠️ Real-time calculation: DISABLED");
        }

        // Check last update time
        if ($customer->balance_last_updated_at) {
            $lastUpdate = $customer->balance_last_updated_at->diffForHumans();
            $this->info("📅 Last updated: {$lastUpdate}");
        } else {
            $this->warn("⚠️ Never updated - run initialization");
        }

        // Check if tables exist and have data
        $monthlyCount = MonthlyCustomerBalance::where('customer_id', $customer->id)->count();
        $transactionCount = TransactionCalculation::where('customer_id', $customer->id)->count();

        $this->info("📊 Monthly balances: {$monthlyCount} records");
        $this->info("📊 Transaction calculations: {$transactionCount} records");
    }

    private function checkDataAvailability(User $customer)
    {
        // Check current month data
        $currentMonth = now()->format('Y-m');
        $currentBalance = MonthlyCustomerBalance::where('customer_id', $customer->id)
            ->where('year_month', $currentMonth)
            ->first();

        if ($currentBalance) {
            $this->info("✅ Current month balance found:");
            $this->info("   Opening: Rp " . number_format($currentBalance->opening_balance, 2));
            $this->info("   Deposits: Rp " . number_format($currentBalance->total_deposits, 2));
            $this->info("   Purchases: Rp " . number_format($currentBalance->total_purchases, 2));
            $this->info("   Closing: Rp " . number_format($currentBalance->closing_balance, 2));
            $this->info("   Volume: " . number_format($currentBalance->total_volume_sm3, 2) . " Sm³");
        } else {
            $this->warn("⚠️ No current month balance found");
        }

        // Check recent transactions
        $recentTransactions = TransactionCalculation::where('customer_id', $customer->id)
            ->orderBy('transaction_date', 'desc')
            ->take(5)
            ->get();

        if ($recentTransactions->count() > 0) {
            $this->info("📋 Recent transactions:");
            foreach ($recentTransactions as $transaction) {
                $this->info("   {$transaction->transaction_date}: " . 
                           number_format($transaction->volume_sm3, 2) . " Sm³, " .
                           "Rp " . number_format($transaction->total_harga, 2));
            }
        } else {
            $this->warn("⚠️ No recent transactions found");
        }
    }

    private function compareCalculations(User $customer)
    {
        // Compare database balance dengan old calculation method
        $currentMonth = now()->format('Y-m');
        
        // Database balance
        $dbBalance = MonthlyCustomerBalance::where('customer_id', $customer->id)
            ->where('year_month', $currentMonth)
            ->first();

        // Old method balance (dari User model)
        $oldBalance = $customer->getCurrentBalance();

        if ($dbBalance) {
            $this->info("💾 Database balance: Rp " . number_format($dbBalance->closing_balance, 2));
            $this->info("🔄 Old method balance: Rp " . number_format($oldBalance, 2));
            
            $difference = abs($dbBalance->closing_balance - $oldBalance);
            if ($difference < 0.01) {
                $this->info("✅ Calculations match!");
            } else {
                $this->warn("⚠️ Difference found: Rp " . number_format($difference, 2));
            }
        } else {
            $this->warn("⚠️ Cannot compare - no database balance found");
        }
    }

    private function performanceTest(User $customer)
    {
        $this->info("⏱️ Performance comparison:");

        // Test 1: Query monthly balance
        $start = microtime(true);
        $dbBalance = MonthlyCustomerBalance::where('customer_id', $customer->id)
            ->where('year_month', now()->format('Y-m'))
            ->first();
        $dbTime = (microtime(true) - $start) * 1000;

        // Test 2: Old calculation method (simplified)
        $start = microtime(true);
        $oldBalance = $customer->getCurrentBalance();
        $oldTime = (microtime(true) - $start) * 1000;

        $this->info("📊 Database query: " . number_format($dbTime, 2) . "ms");
        $this->info("🔄 Old calculation: " . number_format($oldTime, 2) . "ms");

        if ($dbTime < $oldTime) {
            $improvement = (($oldTime - $dbTime) / $oldTime) * 100;
            $this->info("🚀 Performance improvement: " . number_format($improvement, 1) . "%");
        } else {
            $this->warn("⚠️ Database query slower than old method");
        }

        // Test 3: Transaction query performance
        $start = microtime(true);
        $transactions = TransactionCalculation::where('customer_id', $customer->id)
            ->where('year_month', now()->format('Y-m'))
            ->get();
        $transactionTime = (microtime(true) - $start) * 1000;

        $this->info("📋 Transaction query ({$transactions->count()} records): " . 
                   number_format($transactionTime, 2) . "ms");
    }
}
