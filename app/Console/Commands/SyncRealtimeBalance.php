<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MonthlyCustomerBalance;
use App\Models\TransactionCalculation;
use App\Services\RealtimeBalanceService;

class SyncRealtimeBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:sync-realtime 
                            {--customer_id= : Sync specific customer by ID}
                            {--all : Sync all customers}
                            {--from-month= : Start from specific month (Y-m format, e.g., 2024-01)}
                            {--force : Force recalculation even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync and recalculate real-time balance data for customers';

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
        $all = $this->option('all');
        $fromMonth = $this->option('from-month');
        $force = $this->option('force');

        if (!$customerId && !$all) {
            $this->error('Please specify either --customer_id=ID or --all');
            return 1;
        }

        // Validate from-month format
        if ($fromMonth && !preg_match('/^\d{4}-\d{2}$/', $fromMonth)) {
            $this->error('Invalid from-month format. Use Y-m format (e.g., 2024-01)');
            return 1;
        }

        $customers = collect();

        if ($customerId) {
            $customer = User::find($customerId);
            if (!$customer) {
                $this->error("Customer with ID {$customerId} not found");
                return 1;
            }
            if (!$customer->isCustomer() && !$customer->isFOB()) {
                $this->error("User with ID {$customerId} is not a customer or FOB");
                return 1;
            }
            $customers->push($customer);
        } else {
            $customers = User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_FOB])
                ->where('use_realtime_calculation', true)
                ->get();
        }

        if ($customers->isEmpty()) {
            $this->warn('No customers found with real-time calculation enabled');
            return 0;
        }

        $this->info("🔄 Syncing real-time balance data for {$customers->count()} customer(s)...");
        
        if ($fromMonth) {
            $this->info("📅 Starting from month: {$fromMonth}");
        }
        
        if ($force) {
            $this->warn("⚠️  Force mode enabled - existing data will be recalculated");
        }

        $progressBar = $this->output->createProgressBar($customers->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($customers as $customer) {
            try {
                $this->newLine();
                $this->info("🔄 Processing: {$customer->name} (ID: {$customer->id})");

                // Show current statistics
                $currentBalances = MonthlyCustomerBalance::where('customer_id', $customer->id)->count();
                $currentTransactions = TransactionCalculation::where('customer_id', $customer->id)->count(); 
                
                $this->info("   Current data: {$currentBalances} monthly balances, {$currentTransactions} transaction calculations");

                if ($force && ($currentBalances > 0 || $currentTransactions > 0)) {
                    $this->warn("   🗑️  Clearing existing data for recalculation...");
                    
                    // Clear existing data if force mode
                    if ($fromMonth) {
                        MonthlyCustomerBalance::where('customer_id', $customer->id)
                            ->where('year_month', '>=', $fromMonth)
                            ->delete();
                        TransactionCalculation::where('customer_id', $customer->id)
                            ->where('year_month', '>=', $fromMonth)
                            ->delete();
                    } else {
                        MonthlyCustomerBalance::where('customer_id', $customer->id)->delete();
                        TransactionCalculation::where('customer_id', $customer->id)->delete();
                    }
                }

                // Perform sync
                $result = $this->realtimeBalanceService->updateCustomerBalance($customer->id, $fromMonth);

                if ($result) {
                    $successCount++;
                    
                    // Show updated statistics
                    $newBalances = MonthlyCustomerBalance::where('customer_id', $customer->id)->count();
                    $newTransactions = TransactionCalculation::where('customer_id', $customer->id)->count();
                    
                    $this->info("   ✅ Completed: {$newBalances} monthly balances, {$newTransactions} transaction calculations");
                } else {
                    $errorCount++;
                    $errors[] = "Failed to sync customer: {$customer->name} (ID: {$customer->id})";
                }

            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Error syncing {$customer->name} (ID: {$customer->id}): " . $e->getMessage();
                
                $this->newLine();
                $this->error("❌ Error with {$customer->name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("📊 SYNC SUMMARY:");
        $this->info("✅ Successfully synced: {$successCount} customer(s)");
        
        if ($errorCount > 0) {
            $this->error("❌ Failed: {$errorCount} customer(s)");
            $this->newLine();
            $this->error("Error details:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($successCount > 0) {
            $this->newLine();
            $this->info("🎉 Real-time balance sync completed successfully!");
            
            // Show overall statistics
            $totalBalances = MonthlyCustomerBalance::count();
            $totalTransactions = TransactionCalculation::count();
            
            $this->info("📈 Total data in system:");
            $this->info("   - Monthly balances: {$totalBalances}");
            $this->info("   - Transaction calculations: {$totalTransactions}");
        }

        return $errorCount > 0 ? 1 : 0;
    }
}
