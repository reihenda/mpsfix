<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DataPencatatan;
use Illuminate\Support\Facades\DB;

class FixCustomerIdIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:customer-id 
                            {--dry-run : Show what would be fixed without actually fixing}
                            {--auto-assign : Auto assign customer_id based on nama_customer}
                            {--customer_id= : Assign specific customer_id to NULL/zero records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix customer_id issues in data_pencatatan table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $autoAssign = $this->option('auto-assign');
        $specificCustomerId = $this->option('customer_id');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info("🔧 FIXING CUSTOMER_ID ISSUES");
        $this->newLine();

        try {
            DB::beginTransaction();

            // 1. Find records with NULL or zero customer_id
            $problemRecords = DataPencatatan::where(function ($query) {
                $query->whereNull('customer_id')
                      ->orWhere('customer_id', 0);
            })->get();

            $this->info("📊 Found {$problemRecords->count()} records with NULL/zero customer_id");

            if ($problemRecords->isEmpty()) {
                $this->info("✅ No customer_id issues found!");
                return 0;
            }

            $fixed = 0;
            $errors = [];

            if ($autoAssign) {
                // Auto assign based on nama_customer
                $this->info("🤖 Auto-assigning customer_id based on nama_customer...");
                
                foreach ($problemRecords as $record) {
                    try {
                        $customerId = $this->findCustomerIdByName($record->nama_customer);
                        
                        if ($customerId) {
                            if (!$dryRun) {
                                $record->update(['customer_id' => $customerId]);
                                $fixed++;
                            }
                            $this->info("✅ Record {$record->id}: '{$record->nama_customer}' → Customer ID {$customerId}");
                        } else {
                            $this->warn("⚠️  Record {$record->id}: No matching customer found for '{$record->nama_customer}'");
                            $errors[] = "No customer found for: {$record->nama_customer}";
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Error processing record {$record->id}: " . $e->getMessage();
                    }
                }
                
            } elseif ($specificCustomerId) {
                // Assign specific customer_id
                $customer = User::find($specificCustomerId);
                if (!$customer) {
                    $this->error("❌ Customer with ID {$specificCustomerId} not found");
                    return 1;
                }

                if (!$customer->isCustomer() && !$customer->isFOB()) {
                    $this->error("❌ User {$specificCustomerId} is not a customer or FOB");
                    return 1;
                }

                $this->info("🎯 Assigning customer_id {$specificCustomerId} ({$customer->name}) to all problem records...");
                
                foreach ($problemRecords as $record) {
                    if (!$dryRun) {
                        $record->update(['customer_id' => $specificCustomerId]);
                        $fixed++;
                    }
                    $this->info("✅ Record {$record->id}: assigned to {$customer->name}");
                }
                
            } else {
                // Show options for manual fix
                $this->warn("⚠️  Please choose a fix method:");
                $this->info("  --auto-assign    : Auto assign based on nama_customer matching");
                $this->info("  --customer_id=X  : Assign specific customer_id to all problem records");
                $this->newLine();
                
                // Show available customers
                $this->info("📋 Available customers:");
                $customers = User::whereIn('role', ['customer', 'fob'])->get();
                $this->table(
                    ['ID', 'Name', 'Role'],
                    $customers->map(function ($user) {
                        return [$user->id, $user->name, $user->role];
                    })
                );
                
                return 1;
            }

            // 2. Check for invalid customer_id references
            $this->newLine();
            $this->info("🔍 Checking for invalid customer_id references...");
            
            $invalidRefs = DB::table('data_pencatatan as dp')
                ->leftJoin('users as u', 'dp.customer_id', '=', 'u.id')
                ->whereNull('u.id')
                ->whereNotNull('dp.customer_id')
                ->where('dp.customer_id', '!=', 0)
                ->select('dp.id', 'dp.customer_id', 'dp.nama_customer')
                ->get();

            if ($invalidRefs->isNotEmpty()) {
                $this->warn("⚠️  Found {$invalidRefs->count()} records with invalid customer_id references:");
                
                foreach ($invalidRefs as $ref) {
                    $this->warn("  Record {$ref->id}: customer_id {$ref->customer_id} ('{$ref->nama_customer}') doesn't exist");
                    
                    if ($autoAssign) {
                        $validCustomerId = $this->findCustomerIdByName($ref->nama_customer);
                        if ($validCustomerId) {
                            if (!$dryRun) {
                                DB::table('data_pencatatan')
                                    ->where('id', $ref->id)
                                    ->update(['customer_id' => $validCustomerId]);
                                $fixed++;
                            }
                            $this->info("  ✅ Fixed: Record {$ref->id} → Customer ID {$validCustomerId}");
                        }
                    }
                }
            } else {
                $this->info("✅ No invalid customer_id references found");
            }

            if ($dryRun) {
                $this->info("🔍 DRY RUN COMPLETED - {$fixed} records would be fixed");
                DB::rollBack();
            } else {
                DB::commit();
                $this->info("✅ FIXES COMPLETED: {$fixed} records updated");
            }

            if (!empty($errors)) {
                $this->newLine();
                $this->error("❌ Errors encountered:");
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Find customer ID by name matching
     */
    private function findCustomerIdByName($namaCustomer)
    {
        if (empty($namaCustomer)) {
            return null;
        }

        // Try exact match first
        $customer = User::whereIn('role', ['customer', 'fob'])
            ->where('name', $namaCustomer)
            ->first();

        if ($customer) {
            return $customer->id;
        }

        // Try case-insensitive match
        $customer = User::whereIn('role', ['customer', 'fob'])
            ->whereRaw('LOWER(name) = LOWER(?)', [$namaCustomer])
            ->first();

        if ($customer) {
            return $customer->id;
        }

        // Try partial match
        $customer = User::whereIn('role', ['customer', 'fob'])
            ->where('name', 'like', '%' . $namaCustomer . '%')
            ->first();

        return $customer ? $customer->id : null;
    }
}
