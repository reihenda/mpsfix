<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Mendapatkan rate konversi USD ke IDR secara realtime
     * Dengan caching untuk menghindari terlalu banyak request API
     */
    public function getUsdToIdrRate()
    {
        try {
            // Cache rate selama 1 jam untuk menghindari terlalu banyak API calls
            return Cache::remember('usd_to_idr_rate', 3600, function () {
                // Menggunakan exchangerate-api.com (gratis, tidak perlu API key)
                $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/USD');
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['rates']['IDR'])) {
                        return $data['rates']['IDR'];
                    }
                }
                
                // Fallback ke API alternatif jika yang pertama gagal
                return $this->getFallbackRate();
            });
        } catch (\Exception $e) {
            Log::error('Error fetching USD to IDR rate: ' . $e->getMessage());
            
            // Return nilai fallback jika semua API gagal
            return $this->getManualFallbackRate();
        }
    }
    
    /**
     * API fallback alternatif
     */
    private function getFallbackRate()
    {
        try {
            // Menggunakan fixer.io API (gratis dengan limit)
            $response = Http::timeout(10)->get('https://api.fixer.io/latest', [
                'base' => 'USD',
                'symbols' => 'IDR'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rates']['IDR'])) {
                    return $data['rates']['IDR'];
                }
            }
            
            // Jika fixer.io juga gagal, coba API lain
            $response = Http::timeout(10)->get('https://open.er-api.com/v6/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rates']['IDR'])) {
                    return $data['rates']['IDR'];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Fallback API also failed: ' . $e->getMessage());
        }
        
        return $this->getManualFallbackRate();
    }
    
    /**
     * Rate manual sebagai fallback terakhir
     * Update secara berkala sesuai kondisi pasar
     */
    private function getManualFallbackRate()
    {
        // Rate approximate per Agustus 2025 (update sesuai kondisi terkini)
        return 15300;
    }
    
    /**
     * Format rate untuk tampilan
     */
    public function formatRate($rate)
    {
        return number_format($rate, 2, ',', '.');
    }
    
    /**
     * Mendapatkan informasi terakhir update rate
     */
    public function getLastUpdateInfo()
    {
        $lastUpdate = Cache::get('usd_to_idr_rate_timestamp', now());
        
        return [
            'last_update' => $lastUpdate,
            'source' => 'ExchangeRate-API / Manual Fallback',
            'cache_expires' => now()->addHour()
        ];
    }
    
    /**
     * Force refresh rate (hapus cache)
     */
    public function refreshRate()
    {
        Cache::forget('usd_to_idr_rate');
        Cache::forget('usd_to_idr_rate_timestamp');
        
        // Set timestamp untuk tracking
        Cache::put('usd_to_idr_rate_timestamp', now(), 3600);
        
        return $this->getUsdToIdrRate();
    }
}
