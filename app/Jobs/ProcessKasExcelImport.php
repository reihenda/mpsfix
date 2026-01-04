<?php

namespace App\Jobs;

use App\Models\KasTransaction;
use App\Models\FinancialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ProcessKasExcelImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $userId;
    protected $sessionId;
    public $timeout = 300; // 5 minutes timeout per job
    public $tries = 1; // Don't retry on failure

    /**
     * Create a new job instance.
     */
    public function __construct($data, $userId, $sessionId)
    {
        $this->data = $data;
        $this->userId = $userId;
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $progressKey = "kas_import_progress_{$this->sessionId}";
        $errorKey = "kas_import_error_{$this->sessionId}";
        
        try {
            // Initialize progress
            Cache::put($progressKey, [
                'status' => 'processing',
                'current' => 0,
                'total' => count($this->data),
                'message' => 'Memulai proses import...',
                'percentage' => 0,
                'errors' => []
            ], 600); // 10 minutes cache

            // Validate all data first
            $this->updateProgress($progressKey, 'Validasi data Excel...', 5);
            $validationErrors = $this->validateAllData();
            
            if (!empty($validationErrors)) {
                throw new Exception('Validation failed: ' . implode(', ', $validationErrors));
            }

            // Process data in batches
            $this->updateProgress($progressKey, 'Memulai import data...', 10);
            $this->processDataInBatches($progressKey);

            // Success
            Cache::put($progressKey, [
                'status' => 'completed',
                'current' => count($this->data),
                'total' => count($this->data),
                'message' => 'Import berhasil diselesaikan!',
                'percentage' => 100,
                'success_count' => count($this->data),
                'errors' => []
            ], 1800); // 30 menit instead of 10 menit

        } catch (Exception $e) {
            Log::error('Kas Excel Import Failed', [
                'user_id' => $this->userId,
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Cache::put($errorKey, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'timestamp' => now()
            ], 1800); // 30 menit

            Cache::put($progressKey, [
                'status' => 'failed',
                'current' => 0,
                'total' => count($this->data),
                'message' => 'Import gagal: ' . $e->getMessage(),
                'percentage' => 0,
                'errors' => [$e->getMessage()]
            ], 600);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Validate all data before processing
     */
    private function validateAllData()
    {
        $errors = [];
        $accounts = FinancialAccount::active()->ofType('kas')->pluck('account_name')->toArray();
        $vouchersInFile = [];
        
        // Create case-insensitive account mapping
        $accountMapping = [];
        foreach ($accounts as $account) {
            $accountMapping[strtolower($account)] = $account;
        }

        foreach ($this->data as $index => $row) {
            $rowNumber = $index + 4; // +4 because starts from row 4 in Excel
            
            // Validate date
            if (empty($row[0])) {
                $errors[] = "Baris {$rowNumber}: Tanggal tidak boleh kosong";
                continue;
            }
            
            try {
                $date = $this->parseDate($row[0]);
            } catch (Exception $e) {
                $errors[] = "Baris {$rowNumber}: Format tanggal tidak valid. Gunakan format DD/MM/YYYY";
                continue;
            }
            
            // Validate account
            if (empty($row[2])) {
                $errors[] = "Baris {$rowNumber}: Account tidak boleh kosong";
                continue;
            }
            
            $inputAccount = trim($row[2]);
            $inputAccountLower = strtolower($inputAccount);
            
            if (!isset($accountMapping[$inputAccountLower])) {
                $availableAccounts = implode(', ', $accounts);
                $errors[] = "Baris {$rowNumber}: Account '{$inputAccount}' tidak ditemukan. Account tersedia: {$availableAccounts}";
                continue;
            }
            
            // Validate voucher number if provided
            if (!empty($row[1])) {
                $voucherNumber = trim($row[1]);
                
                // Check duplicate in file
                if (in_array($voucherNumber, $vouchersInFile)) {
                    $errors[] = "Baris {$rowNumber}: Voucher '{$voucherNumber}' sudah ada di baris lain dalam file";
                    continue;
                }
                
                // Check duplicate in database
                $existingVoucher = KasTransaction::where('voucher_number', $voucherNumber)->first();
                if ($existingVoucher) {
                    $errors[] = "Baris {$rowNumber}: Voucher '{$voucherNumber}' sudah ada di database";
                    continue;
                }
                
                $vouchersInFile[] = $voucherNumber;
            }
            
            // Validate credit and debit
            $credit = $this->parseNumber($row[4] ?? '');
            $debit = $this->parseNumber($row[5] ?? '');
            
            if ($credit < 0 || $debit < 0) {
                $errors[] = "Baris {$rowNumber}: Credit dan Debit tidak boleh negatif";
                continue;
            }
            
            if ($credit == 0 && $debit == 0) {
                $errors[] = "Baris {$rowNumber}: Minimal salah satu dari Credit atau Debit harus diisi";
                continue;
            }

            // Stop at first 50 errors to prevent memory issues
            if (count($errors) >= 50) {
                $errors[] = "... dan mungkin masih ada error lainnya. Perbaiki error di atas terlebih dahulu.";
                break;
            }
        }
        
        return $errors;
    }

    /**
     * Process data in batches for better performance
     */
    private function processDataInBatches($progressKey)
    {
        $batchSize = 50; // Process 50 rows at a time
        $totalBatches = ceil(count($this->data) / $batchSize);
        $processedCount = 0;
        
        $accounts = FinancialAccount::active()->ofType('kas')->get();
        $accountMapping = [];
        foreach ($accounts as $account) {
            $accountMapping[strtolower($account->account_name)] = $account->id;
        }

        for ($batchIndex = 0; $batchIndex < $totalBatches; $batchIndex++) {
            $batchStart = $batchIndex * $batchSize;
            $batchData = array_slice($this->data, $batchStart, $batchSize);
            
            $this->updateProgress(
                $progressKey, 
                "Memproses batch " . ($batchIndex + 1) . " dari {$totalBatches}...", 
                15 + (($batchIndex / $totalBatches) * 75)
            );

            DB::beginTransaction();
            
            try {
                foreach ($batchData as $row) {
                    $this->processSingleRow($row, $accountMapping);
                    $processedCount++;
                    
                    // Update progress every 10 rows
                    if ($processedCount % 10 == 0) {
                        $percentage = 15 + (($processedCount / count($this->data)) * 75);
                        $this->updateProgress(
                            $progressKey, 
                            "Diproses: {$processedCount} dari " . count($this->data) . " baris", 
                            $percentage
                        );
                    }
                }
                
                DB::commit();
                
                // Clear memory
                unset($batchData);
                if ($batchIndex % 5 == 0) { // Every 5 batches
                    gc_collect_cycles();
                }
                
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception("Error pada batch " . ($batchIndex + 1) . ": " . $e->getMessage());
            }
        }

        // Final step: Recalculate all balances efficiently
        $this->updateProgress($progressKey, 'Menghitung ulang saldo...', 90);
        $this->recalculateAllBalances();
    }

    /**
     * Process a single row of data
     */
    private function processSingleRow($row, $accountMapping)
    {
        $transactionDate = $this->parseDate($row[0]);
        $inputAccount = trim($row[2]);
        $description = trim($row[3] ?? '') ?: null;
        $credit = $this->parseNumber($row[4] ?? '');
        $debit = $this->parseNumber($row[5] ?? '');
        
        // Get account ID
        $accountId = $accountMapping[strtolower($inputAccount)];
        
        // Generate or use provided voucher number
        if (!empty($row[1])) {
            $voucherNumber = trim($row[1]);
        } else {
            $voucherNumber = $this->generateUniqueVoucherNumber($transactionDate->year);
        }
        
        // Create transaction (balance will be calculated later)
        $transaction = new KasTransaction([
            'voucher_number' => $voucherNumber,
            'account_id' => $accountId,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'credit' => $credit,
            'debit' => $debit,
            'balance' => 0, // Will be calculated later
            'year' => $transactionDate->year,
            'month' => $transactionDate->month,
        ]);
        
        $transaction->save();
    }

    /**
     * Generate unique voucher number with proper locking
     */
    private function generateUniqueVoucherNumber($year)
    {
        // Use database locking to prevent duplicates
        return DB::transaction(function () use ($year) {
            $lastVoucher = KasTransaction::where('year', $year)
                ->where('voucher_number', 'LIKE', 'KAS%')
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(voucher_number, 4) AS UNSIGNED) DESC')
                ->first();
            
            if (!$lastVoucher) {
                return 'KAS0001';
            }
            
            $lastNumber = (int) substr($lastVoucher->voucher_number, 3);
            $newNumber = $lastNumber + 1;
            
            return 'KAS' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Efficiently recalculate all balances
     */
    private function recalculateAllBalances()
    {
        // Get all transactions ordered by date and ID
        $transactions = KasTransaction::orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        
        $runningBalance = 0;
        $updates = [];
        
        foreach ($transactions as $transaction) {
            $runningBalance += $transaction->credit - $transaction->debit;
            $updates[] = [
                'id' => $transaction->id,
                'balance' => $runningBalance
            ];
        }
        
        // Batch update balances
        $chunks = array_chunk($updates, 100);
        foreach ($chunks as $chunk) {
            $cases = [];
            $ids = [];
            
            foreach ($chunk as $update) {
                $cases[] = "WHEN {$update['id']} THEN {$update['balance']}";
                $ids[] = $update['id'];
            }
            
            $casesStr = implode(' ', $cases);
            $idsStr = implode(',', $ids);
            
            DB::statement("
                UPDATE kas_transactions 
                SET balance = CASE id {$casesStr} END 
                WHERE id IN ({$idsStr})
            ");
        }
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($key, $message, $percentage)
    {
        $current = Cache::get($key, []);
        $current['message'] = $message;
        $current['percentage'] = round($percentage, 1);
        Cache::put($key, $current, 600);
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            throw new Exception('Tanggal tidak boleh kosong');
        }
        
        $value = trim(strval($value));
        
        // DD/MM/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            
            if (!checkdate($month, $day, $year)) {
                throw new Exception('Tanggal tidak valid');
            }
            
            return Carbon::createFromFormat('d/m/Y', $value);
        }
        
        // DD-MM-YYYY format
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            
            if (!checkdate($month, $day, $year)) {
                throw new Exception('Tanggal tidak valid');
            }
            
            return Carbon::createFromFormat('d-m-Y', $value);
        }
        
        // Excel serial number
        if (is_numeric($value)) {
            $unixTimestamp = ($value - 25569) * 86400;
            return Carbon::createFromTimestamp($unixTimestamp);
        }
        
        throw new Exception('Format tanggal tidak didukung. Gunakan format DD/MM/YYYY');
    }

    /**
     * Parse number from string
     */
    private function parseNumber($value)
    {
        if (empty($value)) {
            return 0;
        }
        
        $value = trim(strval($value));
        
        if (empty($value)) {
            return 0;
        }
        
        // Remove non-numeric characters except comma and dot
        $value = preg_replace('/[^0-9.,]/', '', $value);
        
        // Handle different number formats
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');
            
            if ($lastComma > $lastDot) {
                // Indonesian format: 1.000.000,50
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                // International format: 1,000,000.50
                $value = str_replace(',', '', $value);
            }
        } elseif (strpos($value, ',') !== false) {
            // Only comma - check if decimal or thousands
            $parts = explode(',', $value);
            $lastPart = end($parts);
            
            if (strlen($lastPart) <= 2) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (strpos($value, '.') !== false) {
            // Only dot - check if decimal or thousands
            $parts = explode('.', $value);
            $lastPart = end($parts);
            
            if (strlen($lastPart) > 2 || count($parts) > 2) {
                $value = str_replace('.', '', $value);
            }
        }
        
        return (float) $value;
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        $errorKey = "kas_import_error_{$this->sessionId}";
        $progressKey = "kas_import_progress_{$this->sessionId}";
        
        Cache::put($errorKey, [
            'status' => 'failed',
            'message' => $exception->getMessage(),
            'timestamp' => now()
        ], 600);

        Cache::put($progressKey, [
            'status' => 'failed',
            'current' => 0,
            'total' => count($this->data),
            'message' => 'Import gagal: ' . $exception->getMessage(),
            'percentage' => 0,
            'errors' => [$exception->getMessage()]
        ], 600);
    }
}
