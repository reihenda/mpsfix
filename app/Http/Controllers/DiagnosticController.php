<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

class DiagnosticController extends Controller
{
    /**
     * Display diagnostic information for troubleshooting
     */
    public function index()
    {
        $info = [
            'tables' => [],
            'routes' => [],
            'ukuran_data' => [],
            'environment' => app()->environment(),
            'database_connection' => config('database.default'),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'is_debug' => config('app.debug'),
        ];
        
        // Check database tables
        try {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . config('database.connections.mysql.database');
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $info['tables'][$tableName] = [
                    'exists' => true,
                    'count' => DB::table($tableName)->count(),
                    'columns' => Schema::getColumnListing($tableName)
                ];
            }
            
            // Get specific ukuran data
            if (Schema::hasTable('ukuran_truk')) {
                $info['ukuran_data'] = DB::table('ukuran_truk')->get()->toArray();
            }
        } catch (\Exception $e) {
            $info['db_error'] = $e->getMessage();
        }
        
        // Get relevant routes
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            if (strpos($route->uri, 'ukuran') !== false || strpos($route->uri, 'diagnostic') !== false) {
                $info['routes'][] = [
                    'methods' => $route->methods,
                    'uri' => $route->uri,
                    'name' => $route->getName(),
                    'action' => $route->getActionName()
                ];
            }
        }
        
        return view('diagnostic.index', compact('info'));
    }
    
    /**
     * Clear application caches
     */
    public function clearCache()
    {
        $results = [];
        
        try {
            Artisan::call('route:clear');
            $results['route_cache'] = 'Route cache cleared successfully';
        } catch (\Exception $e) {
            $results['route_cache'] = 'Error: ' . $e->getMessage();
        }
        
        try {
            Artisan::call('config:clear');
            $results['config_cache'] = 'Config cache cleared successfully';
        } catch (\Exception $e) {
            $results['config_cache'] = 'Error: ' . $e->getMessage();
        }
        
        try {
            Artisan::call('view:clear');
            $results['view_cache'] = 'View cache cleared successfully';
        } catch (\Exception $e) {
            $results['view_cache'] = 'Error: ' . $e->getMessage();
        }
        
        try {
            Artisan::call('cache:clear');
            $results['application_cache'] = 'Application cache cleared successfully';
        } catch (\Exception $e) {
            $results['application_cache'] = 'Error: ' . $e->getMessage();
        }
        
        return redirect()->route('diagnostic.index')->with('results', $results);
    }
    
    /**
     * Test database connection
     */
    public function testDB()
    {
        $result = [
            'success' => false,
            'message' => '',
            'tables' => []
        ];
        
        try {
            // Test connection
            DB::connection()->getPdo();
            $result['success'] = true;
            $result['message'] = 'Database connection successful. Connected to ' . DB::connection()->getDatabaseName();
            
            // Get tables info
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . config('database.connections.mysql.database');
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $result['tables'][$tableName] = [
                    'count' => DB::table($tableName)->count(),
                    'columns' => Schema::getColumnListing($tableName)
                ];
            }
        } catch (\Exception $e) {
            $result['message'] = 'Database connection failed: ' . $e->getMessage();
        }
        
        return response()->json($result);
    }
}
