<?php
// initialize_realtime.php
// Script untuk initialize sistem real-time balance

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\RealtimeBalanceService;

echo "=== INITIALIZE REAL-TIME BALANCE SYSTEM ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $realtimeService = app(RealtimeBalanceService::class);
    
    // Get customers
    $customers = User::whereIn('role', ['customer', 'fob'])->get();
    
    echo "Found {$customers->count()} customers\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($customers as $customer) {
        echo "Processing: {$customer->name} (ID: {$customer->id})\n";
        
        try {
            $result = $realtimeService->initializeCustomer($customer->id);
            
            if ($result) {
                $successCount++;
                echo "  ✅ SUCCESS\n";
            } else {
                $errorCount++;
                echo "  ❌ FAILED\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== SUMMARY ===\n";
    echo "✅ Success: {$successCount}\n";
    echo "❌ Failed: {$errorCount}\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>