<?php

namespace App\Observers;

use App\Models\DataPencatatan;
use App\Services\RealtimeBalanceService;
use Illuminate\Support\Facades\Log;

class DataPencatatanObserver
{
    protected $realtimeBalanceService;

    public function __construct(RealtimeBalanceService $realtimeBalanceService)
    {
        $this->realtimeBalanceService = $realtimeBalanceService;
    }

    /**
     * Handle the DataPencatatan "created" event.
     */
    public function created(DataPencatatan $dataPencatatan): void
    {
        // Skip jika customer tidak menggunakan real-time calculation
        if (!$this->shouldProcessRealtime($dataPencatatan)) {
            return;
        }

        Log::info('DataPencatatan created - updating real-time balance', [
            'data_pencatatan_id' => $dataPencatatan->id,
            'customer_id' => $dataPencatatan->customer_id
        ]);

        $this->realtimeBalanceService->onDataPencatatanChanged($dataPencatatan, 'created');
    }

    /**
     * Handle the DataPencatatan "updated" event.
     */
    public function updated(DataPencatatan $dataPencatatan): void
    {
        // Skip jika customer tidak menggunakan real-time calculation
        if (!$this->shouldProcessRealtime($dataPencatatan)) {
            return;
        }

        Log::info('DataPencatatan updated - updating real-time balance', [
            'data_pencatatan_id' => $dataPencatatan->id,
            'customer_id' => $dataPencatatan->customer_id,
            'changes' => $dataPencatatan->getChanges()
        ]);

        $this->realtimeBalanceService->onDataPencatatanChanged($dataPencatatan, 'updated');
    }

    /**
     * Handle the DataPencatatan "deleted" event.
     */
    public function deleted(DataPencatatan $dataPencatatan): void
    {
        // Skip jika customer tidak menggunakan real-time calculation
        if (!$this->shouldProcessRealtime($dataPencatatan)) {
            return;
        }

        Log::info('DataPencatatan deleted - updating real-time balance', [
            'data_pencatatan_id' => $dataPencatatan->id,
            'customer_id' => $dataPencatatan->customer_id
        ]);

        $this->realtimeBalanceService->onDataPencatatanChanged($dataPencatatan, 'deleted');
    }

    /**
     * Check apakah customer menggunakan real-time calculation
     */
    private function shouldProcessRealtime(DataPencatatan $dataPencatatan): bool
    {
        if (!$dataPencatatan->customer_id) {
            return false;
        }

        $customer = $dataPencatatan->customer;
        if (!$customer) {
            return false;
        }

        // Check apakah customer menggunakan real-time calculation
        return $customer->use_realtime_calculation ?? false;
    }
}
