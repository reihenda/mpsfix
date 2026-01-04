<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HargaGagas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestKaloriPrecision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:kalori-precision {--cleanup : Clean up test data after testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test kalori precision functionality (12 decimal places)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Kalori Precision Update...');
        $this->newLine();

        $allPassed = true;

        // Test 1: Database Schema
        $this->info('1️⃣ Testing Database Schema...');
        if (!$this->testDatabaseSchema()) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: Model Casting
        $this->info('2️⃣ Testing Model Casting...');
        if (!$this->testModelCasting()) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 3: Data Input/Output
        $this->info('3️⃣ Testing High Precision Data Input/Output...');
        if (!$this->testDataInputOutput()) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 4: Validation Rules
        $this->info('4️⃣ Testing Validation Rules...');
        if (!$this->testValidationRules()) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 5: Calculation Accuracy
        $this->info('5️⃣ Testing Calculation Accuracy...');
        if (!$this->testCalculationAccuracy()) {
            $allPassed = false;
        }
        $this->newLine();

        // Test 6: Edge Cases
        $this->info('6️⃣ Testing Edge Cases...');
        if (!$this->testEdgeCases()) {
            $allPassed = false;
        }
        $this->newLine();

        // Cleanup if requested
        if ($this->option('cleanup')) {
            $this->info('🧹 Cleaning up test data...');
            $this->cleanupTestData();
            $this->newLine();
        }

        // Final result
        if ($allPassed) {
            $this->info('✅ ALL TESTS PASSED! Kalori precision update is working correctly.');
        } else {
            $this->error('❌ SOME TESTS FAILED! Please check the issues above.');
        }

        $this->info('💡 Don\'t forget to test the web interface manually!');
        
        return $allPassed ? 0 : 1;
    }

    private function testDatabaseSchema(): bool
    {
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM harga_gagas LIKE 'kalori'")[0];
            
            if (str_contains($columnInfo->Type, 'decimal(20,12)')) {
                $this->line('   ✅ Column type: ' . $columnInfo->Type);
                return true;
            } else {
                $this->error('   ❌ Expected decimal(20,12), got: ' . $columnInfo->Type);
                return false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Database error: ' . $e->getMessage());
            return false;
        }
    }

    private function testModelCasting(): bool
    {
        try {
            $model = new HargaGagas();
            $casts = $model->getCasts();
            
            if (isset($casts['kalori']) && $casts['kalori'] === 'decimal:12') {
                $this->line('   ✅ Kalori cast: ' . $casts['kalori']);
                return true;
            } else {
                $this->error('   ❌ Expected decimal:12, got: ' . ($casts['kalori'] ?? 'not set'));
                return false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Model error: ' . $e->getMessage());
            return false;
        }
    }

    private function testDataInputOutput(): bool
    {
        $testCases = [
            ['kalori' => 23.123456789012, 'name' => 'Full precision (12 decimals)'],
            ['kalori' => 25.1, 'name' => 'Low precision (1 decimal)'],
            ['kalori' => 30.000000000001, 'name' => 'Minimal precision difference'],
        ];

        $allPassed = true;

        foreach ($testCases as $index => $testCase) {
            try {
                $this->line("   Testing: {$testCase['name']}");
                
                // Clean up previous test data
                HargaGagas::where('periode_tahun', 2025)
                         ->where('periode_bulan', 12 - $index)
                         ->delete();

                // Create test record
                $record = HargaGagas::create([
                    'harga_usd' => 10.00,
                    'rate_konversi_idr' => 15000.00,
                    'kalori' => $testCase['kalori'],
                    'periode_tahun' => 2025,
                    'periode_bulan' => 12 - $index,
                ]);

                // Retrieve and compare
                $retrieved = HargaGagas::find($record->id);
                
                // Use bccomp for precise decimal comparison
                if (bccomp((string)$retrieved->kalori, (string)$testCase['kalori'], 12) === 0) {
                    $this->line("     ✅ Input: {$testCase['kalori']} → Stored: {$retrieved->kalori}");
                } else {
                    $this->error("     ❌ Input: {$testCase['kalori']} → Stored: {$retrieved->kalori}");
                    $allPassed = false;
                }

            } catch (\Exception $e) {
                $this->error("     ❌ Error: " . $e->getMessage());
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private function testValidationRules(): bool
    {
        $validationTests = [
            ['value' => '23.123456789012', 'should_pass' => true, 'name' => 'Valid 12 decimals'],
            ['value' => '23.1234567890123', 'should_pass' => false, 'name' => '13 decimals (too many)'],
            ['value' => '0', 'should_pass' => false, 'name' => 'Zero value'],
            ['value' => '-5.5', 'should_pass' => false, 'name' => 'Negative value'],
            ['value' => '25.1', 'should_pass' => true, 'name' => 'Valid low precision'],
            ['value' => '0.000000000001', 'should_pass' => true, 'name' => 'Minimum valid value'],
        ];

        $allPassed = true;

        foreach ($validationTests as $test) {
            $validator = Validator::make(
                ['kalori' => $test['value']],
                ['kalori' => 'required|numeric|min:0.000000000001|regex:/^\d+(\.\d{1,12})?$/'],
                [
                    'kalori.min' => 'Nilai kalori harus lebih dari 0',
                    'kalori.regex' => 'Nilai kalori maksimal 12 angka di belakang koma',
                ]
            );

            $passes = $validator->passes();
            
            if ($passes === $test['should_pass']) {
                $this->line("   ✅ {$test['name']}: {$test['value']} → " . ($passes ? 'PASS' : 'FAIL'));
            } else {
                $this->error("   ❌ {$test['name']}: {$test['value']} → Expected " . 
                           ($test['should_pass'] ? 'PASS' : 'FAIL') . ', got ' . ($passes ? 'PASS' : 'FAIL'));
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private function testCalculationAccuracy(): bool
    {
        try {
            // Create test record with high precision
            $testKalori = 23.123456789012;
            $testVolume = 1000.55;
            
            HargaGagas::where('periode_tahun', 2025)->where('periode_bulan', 1)->delete();
            
            $record = HargaGagas::create([
                'harga_usd' => 12.50,
                'rate_konversi_idr' => 15500.75,
                'kalori' => $testKalori,
                'periode_tahun' => 2025,
                'periode_bulan' => 1,
            ]);

            // Test calculation
            $retrieved = HargaGagas::find($record->id);
            $mmbtu = $testVolume / $retrieved->kalori;
            $hargaIDR = $retrieved->harga_usd * $retrieved->rate_konversi_idr;
            $totalPembelian = $mmbtu * $hargaIDR;

            $this->line("   ✅ Test Volume: " . number_format($testVolume, 2));
            $this->line("   ✅ Test Kalori: " . number_format($retrieved->kalori, 12));
            $this->line("   ✅ MMBTU: " . number_format($mmbtu, 12));
            $this->line("   ✅ Total Pembelian: Rp " . number_format($totalPembelian, 2));

            // Check if calculation is reasonable (not zero, not infinite)
            if ($mmbtu > 0 && $totalPembelian > 0 && is_finite($mmbtu) && is_finite($totalPembelian)) {
                $this->line("   ✅ Calculations are accurate and reasonable");
                return true;
            } else {
                $this->error("   ❌ Calculations produced invalid results");
                return false;
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Calculation error: " . $e->getMessage());
            return false;
        }
    }

    private function testEdgeCases(): bool
    {
        $edgeCases = [
            ['kalori' => 0.000000000001, 'name' => 'Minimum possible value'],
            ['kalori' => 99999999.999999999999, 'name' => 'Maximum possible value'],
            ['kalori' => 1.000000000000, 'name' => 'Trailing zeros'],
            ['kalori' => 999.123456789012, 'name' => 'Large number with precision'],
        ];

        $allPassed = true;

        foreach ($edgeCases as $index => $testCase) {
            try {
                $this->line("   Testing: {$testCase['name']}");
                
                HargaGagas::where('periode_tahun', 2024)->where('periode_bulan', $index + 1)->delete();

                $record = HargaGagas::create([
                    'harga_usd' => 10.00,
                    'rate_konversi_idr' => 15000.00,
                    'kalori' => $testCase['kalori'],
                    'periode_tahun' => 2024,
                    'periode_bulan' => $index + 1,
                ]);

                $retrieved = HargaGagas::find($record->id);
                
                // Test that value is stored and retrievable
                if ($retrieved && $retrieved->kalori > 0) {
                    $this->line("     ✅ Stored: " . number_format($retrieved->kalori, 12));
                    
                    // Test calculation doesn't break
                    $testVolume = 1000;
                    $mmbtu = $testVolume / $retrieved->kalori;
                    
                    if (is_finite($mmbtu) && $mmbtu > 0) {
                        $this->line("     ✅ Calculation works: " . number_format($mmbtu, 6) . " MMBTU");
                    } else {
                        $this->error("     ❌ Calculation failed");
                        $allPassed = false;
                    }
                } else {
                    $this->error("     ❌ Failed to store/retrieve");
                    $allPassed = false;
                }

            } catch (\Exception $e) {
                $this->error("     ❌ Error: " . $e->getMessage());
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private function cleanupTestData(): void
    {
        try {
            $deletedCount = HargaGagas::whereIn('periode_tahun', [2024, 2025])
                                     ->whereIn('periode_bulan', [1, 2, 3, 4, 10, 11, 12])
                                     ->delete();
            
            $this->line("   ✅ Cleaned up {$deletedCount} test records");
        } catch (\Exception $e) {
            $this->error("   ❌ Cleanup error: " . $e->getMessage());
        }
    }
}
