<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MonthlyCustomerBalance;
use Illuminate\Support\Facades\DB;

class SyncMonthlyBalancesToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:sync-monthly-to-users {--customer_id=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync closing_balance dari monthly_customer_balances ke monthly_balances di users table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->option('customer_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no data will be changed');
        }

        try {
            DB::beginTransaction();

            // Build query
            $query = User::whereIn('role', ['customer', 'fob']);
            
            if ($customerId) {
                $query->where('id', $customerId);
            }

            $customers = $query->get();

            $this->info("📊 Processing {$customers->count()} customers...");

            $processed = 0;
            $updated = 0;

            foreach ($customers as $customer) {
                $this->info("Processing customer: {$customer->name} (ID: {$customer->id})");

                // Ambil semua monthly balances untuk customer ini
                $monthlyBalances = MonthlyCustomerBalance::where('customer_id', $customer->id)
                    ->orderBy('year_month')
                    ->get();

                if ($monthlyBalances->isEmpty()) {
                    $this->warn("  ⚠️  No monthly balances found");
                    continue;
                }

                // Siapkan array untuk disimpan di users.monthly_balances
                $userMonthlyBalances = [];

                foreach ($monthlyBalances as $balance) {
                    $userMonthlyBalances[$balance->year_month] = floatval($balance->closing_balance);
                }

                // Show what will be synced
                $this->info("  📅 Found " . count($userMonthlyBalances) . " periods:");
                foreach ($userMonthlyBalances as $period => $balance) {
                    $this->info("    {$period}: Rp " . number_format($balance, 2));
                }

                if (!$dryRun) {
                    // Update monthly_balances di tabel users
                    $customer->update([
                        'monthly_balances' => $userMonthlyBalances
                    ]);
                    $updated++;
                }

                $processed++;
            }

            if ($dryRun) {
                $this->info("✅ DRY RUN completed. {$processed} customers would be updated.");
                DB::rollBack();
            } else {
                DB::commit();
                $this->info("✅ Sync completed successfully!");
                $this->info("📊 Processed: {$processed} customers");
                $this->info("🔄 Updated: {$updated} customers");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
