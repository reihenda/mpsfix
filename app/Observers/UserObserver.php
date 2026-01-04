<?php

namespace App\Observers;

use App\Models\User;
use App\Services\RealtimeBalanceService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected $realtimeBalanceService;

    public function __construct(RealtimeBalanceService $realtimeBalanceService)
    {
        $this->realtimeBalanceService = $realtimeBalanceService;
    }

    /**
     * Handle the User "updated" event untuk pricing changes.
     */
    public function updated(User $user): void
    {
        // Skip jika bukan customer atau tidak menggunakan real-time calculation
        if (!$this->shouldProcessRealtime($user)) {
            return;
        }

        // Check apakah ada perubahan pada pricing fields
        $pricingFields = [
            'harga_per_meter_kubik',
            'tekanan_keluar',
            'suhu',
            'koreksi_meter',
            'pricing_history',
            'deposit_history',
            'total_deposit'
        ];

        $pricingChanged = false;
        $depositChanged = false;
        $changedFields = [];

        foreach ($pricingFields as $field) {
            if ($user->wasChanged($field)) {
                $changedFields[] = $field;
                
                if (in_array($field, ['harga_per_meter_kubik', 'tekanan_keluar', 'suhu', 'koreksi_meter', 'pricing_history'])) {
                    $pricingChanged = true;
                }
                
                if (in_array($field, ['deposit_history', 'total_deposit'])) {
                    $depositChanged = true;
                }
            }
        }

        if ($pricingChanged || $depositChanged) {
            Log::info('User pricing/deposit changed - updating real-time balance', [
                'user_id' => $user->id,
                'changed_fields' => $changedFields,
                'pricing_changed' => $pricingChanged,
                'deposit_changed' => $depositChanged
            ]);

            if ($pricingChanged) {
                // Untuk perubahan pricing, recalculate dari bulan ini atau bulan efektif
                $this->realtimeBalanceService->onPricingChanged($user->id, now());
            }

            if ($depositChanged) {
                // Untuk perubahan deposit, update dari bulan terbaru
                $this->realtimeBalanceService->onDepositChanged($user->id, now());
            }
        }
    }

    /**
     * Check apakah user menggunakan real-time calculation
     */
    private function shouldProcessRealtime(User $user): bool
    {
        // Hanya process untuk customer atau FOB
        if (!$user->isCustomer() && !$user->isFOB()) {
            return false;
        }

        // Check apakah user menggunakan real-time calculation
        return $user->use_realtime_calculation ?? false;
    }
}
