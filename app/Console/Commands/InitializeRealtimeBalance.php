<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\RealtimeBalanceService;
use Illuminate\Support\Facades\DB;

class InitializeRealtimeBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:initialize-realtime 
                            {--customer_id= : Initialize specific customer by ID}
                            {--all : Initialize all customers}
                            {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize real-time balance calculation system for existing customers';

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
        $dryRun = $this->option('dry-run');

        if (!$customerId && !$all) {
            $this->error('Please specify either --customer_id=ID or --all');
            return 1;
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
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
            $customers = User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_FOB])->get();
        }

        $this->info("🚀 Initializing real-time balance system for {$customers->count()} customer(s)...");

        $progressBar = $this->output->createProgressBar($customers->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($customers as $customer) {
            try {
                if ($dryRun) {
                    // Dry run - hanya show info
                    $dataCount = $customer->dataPencatatan()->count();
                    $depositCount = count($customer->deposit_history ?? []);
                    
                    $this->newLine();
                    $this->info("📊 Customer: {$customer->name} (ID: {$customer->id})");
                    $this->info("   - Data Pencatatan: {$dataCount} records");
                    $this->info("   - Deposit History: {$depositCount} entries");
                    $this->info("   - Current Total Deposit: " . number_format($customer->total_deposit ?? 0, 2));
                    $this->info("   - Current Total Purchases: " . number_format($customer->total_purchases ?? 0, 2));
                } else {
                    // Actual initialization
                    $result = $this->realtimeBalanceService->initializeCustomer($customer->id);
                    
                    if ($result) {
                        $successCount++;
                        $this->newLine();
                        $this->info("✅ Successfully initialized: {$customer->name} (ID: {$customer->id})");
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to initialize customer: {$customer->name} (ID: {$customer->id})";
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Error initializing {$customer->name} (ID: {$customer->id}): " . $e->getMessage();
                
                $this->newLine();
                $this->error("❌ Error with {$customer->name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("🔍 DRY RUN COMPLETED - No changes were made");
            $this->info("📊 {$customers->count()} customer(s) would be processed");
        } else {
            // Summary
            $this->info("📊 INITIALIZATION SUMMARY:");
            $this->info("✅ Successfully initialized: {$successCount} customer(s)");
            
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
                $this->info("🎉 Real-time balance system has been initialized!");
                $this->info("💡 You can now query balance data from database tables:");
                $this->info("   - monthly_customer_balances: For monthly balance summaries");
                $this->info("   - transaction_calculations: For detailed transaction calculations");
            }
        }

        return $errorCount > 0 ? 1 : 0;
    }
}
