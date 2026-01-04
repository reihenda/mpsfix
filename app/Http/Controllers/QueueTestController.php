<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class QueueTestController extends Controller
{
    /**
     * Manually process queue for testing purposes
     */
    public function processQueue()
    {
        try {
            // Process queue manually
            $exitCode = Artisan::call('queue:work', [
                '--max-jobs' => 5,
                '--max-time' => 60,
                '--timeout' => 60
            ]);
            
            $output = Artisan::output();
            
            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Queue processed successfully' : 'Queue processing failed',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check cache contents for debugging
     */
    public function checkCache(Request $request)
    {
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            return response()->json(['error' => 'Session ID required'], 400);
        }
        
        $progressKey = "kas_import_progress_{$sessionId}";
        $errorKey = "kas_import_error_{$sessionId}";
        
        return response()->json([
            'progress' => Cache::get($progressKey),
            'error' => Cache::get($errorKey),
            'cache_driver' => config('cache.default')
        ]);
    }
}
