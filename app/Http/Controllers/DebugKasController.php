<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DebugKasController extends Controller
{
    public function checkSystem()
    {
        $results = [];
        
        // 1. Check database connection
        try {
            DB::connection()->getPdo();
            $results['database'] = '✅ Connected';
        } catch (\Exception $e) {
            $results['database'] = '❌ Error: ' . $e->getMessage();
        }
        
        // 2. Check tables exist
        $results['tables'] = [];
        $requiredTables = ['jobs', 'failed_jobs', 'kas_transactions', 'financial_accounts'];
        
        foreach ($requiredTables as $table) {
            $results['tables'][$table] = Schema::hasTable($table) ? '✅ Exists' : '❌ Missing';
        }
        
        // 3. Check cache
        try {
            Cache::put('test_key', 'test_value', 60);
            $cached = Cache::get('test_key');
            $results['cache'] = $cached === 'test_value' ? '✅ Working' : '❌ Not working';
            Cache::forget('test_key');
        } catch (\Exception $e) {
            $results['cache'] = '❌ Error: ' . $e->getMessage();
        }
        
        // 4. Check queue jobs count
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $results['queue'] = [
                'pending' => $pendingJobs,
                'failed' => $failedJobs
            ];
        } catch (\Exception $e) {
            $results['queue'] = '❌ Error: ' . $e->getMessage();
        }
        
        // 5. Check accounts
        try {
            $accounts = DB::table('financial_accounts')
                ->where('is_active', 1)
                ->where('account_type', 'kas')
                ->count();
            $results['kas_accounts'] = $accounts > 0 ? "✅ Found {$accounts} accounts" : '❌ No kas accounts';
        } catch (\Exception $e) {
            $results['kas_accounts'] = '❌ Error: ' . $e->getMessage();
        }
        
        // 6. Check recent import sessions
        $importSessions = [];
        for ($i = 0; $i < 10; $i++) {
            $sessionId = "kas_import_progress_" . session()->getId() . "_" . $i;
            $progress = Cache::get($sessionId);
            if ($progress) {
                $importSessions[] = [
                    'session' => $sessionId,
                    'data' => $progress
                ];
            }
        }
        $results['recent_imports'] = $importSessions;
        
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
    
    public function checkLastImport(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            // Try to find any recent import
            $allKeys = Cache::getStore()->getMemcached()->getAllKeys();
            $importKeys = array_filter($allKeys, function($key) {
                return strpos($key, 'kas_import_progress_') !== false;
            });
            
            return response()->json([
                'message' => 'No session_id provided',
                'available_sessions' => $importKeys
            ]);
        }
        
        $progressKey = "kas_import_progress_{$sessionId}";
        $errorKey = "kas_import_error_{$sessionId}";
        
        return response()->json([
            'progress' => Cache::get($progressKey),
            'error' => Cache::get($errorKey),
            'cache_driver' => config('cache.default')
        ]);
    }
    
    public function simulateImport()
    {
        // Create a test import session
        $sessionId = 'test_' . time();
        $progressKey = "kas_import_progress_{$sessionId}";
        
        Cache::put($progressKey, [
            'status' => 'processing',
            'current' => 50,
            'total' => 126,
            'message' => 'Testing import simulation...',
            'percentage' => 39.7,
            'errors' => []
        ], 600);
        
        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Test import session created',
            'check_url' => route('debug.check-import', ['session_id' => $sessionId])
        ]);
    }
}
