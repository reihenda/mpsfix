    // Method untuk mencetak billing dalam bentuk PDF
    public function printBilling(Request $request, User $customer)
    {
        // Get filter parameters
        $bulan = $request->input('bulan', now()->month);
        $tahun = $request->input('tahun', now()->year);

        // Format filter untuk query
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Get pricing info for selected month
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

        // Base query
        $query = $customer->dataPencatatan();

        // Ambil semua data dulu
        $dataPencatatan = $query->get();

        // Filter data berdasarkan bulan dan tahun dari pembacaan awal
        $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth) {
            $dataInput = $this->ensureArray($item->data_input);

            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                return false;
            }

            // Convert the timestamp to year-month format for comparison
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

            // Filter by year-month
            return $waktuAwal === $yearMonth;
        });

        // Perhitungan untuk volume dan biaya pemakaian gas
        $pemakaianGas = [];
        $totalVolume = 0;
        $totalBiaya = 0;

        $i = 1;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaGas = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $biayaPemakaian = $volumeSm3 * $hargaGas;

            $periodeMulai = isset($dataInput['pembacaan_awal']['waktu']) ? 
                            Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d/m/Y') : '';
            $periodeSelesai = isset($dataInput['pembacaan_akhir']['waktu']) ? 
                              Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d/m/Y') : '';
            $periodePemakaian = $periodeMulai . ' - ' . $periodeSelesai;

            $pemakaianGas[] = [
                'no' => $i++,
                'periode_pemakaian' => $periodePemakaian,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian
            ];

            $totalVolume += $volumeSm3;
            $totalBiaya += $biayaPemakaian;
        }

        // Perhitungan untuk penerimaan deposit
        $penerimaanDeposit = [];
        $totalDeposit = 0;

        $depositHistory = $this->ensureArray($customer->deposit_history);
        $j = 1;
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->month == $bulan && $depositDate->year == $tahun) {
                    $jumlahDeposit = floatval($deposit['amount'] ?? 0);
                    $penerimaanDeposit[] = [
                        'no' => $j++,
                        'tanggal_deposit' => $depositDate->format('d/m/Y'),
                        'jumlah_penerimaan' => $jumlahDeposit
                    ];
                    $totalDeposit += $jumlahDeposit;
                }
            }
        }

        // Menghitung saldo bulan sebelumnya
        $prevDate = Carbon::createFromDate($tahun, $bulan, 1)->subMonth();
        $prevMonthYear = $prevDate->format('Y-m');
        
        // Mendapatkan deposit dan pembelian pada semua periode sebelumnya
        $prevTotalDeposits = 0;
        $prevTotalPurchases = 0;
        
        // Menghitung deposit seluruh periode sebelumnya
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate < Carbon::createFromDate($tahun, $bulan, 1)) {
                    $prevTotalDeposits += floatval($deposit['amount'] ?? 0);
                }
            }
        }
        
        // Menghitung pembelian seluruh periode sebelumnya
        $allData = $customer->dataPencatatan()->get();
        foreach ($allData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            
            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }
            
            $itemDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            if ($itemDate < Carbon::createFromDate($tahun, $bulan, 1)) {
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                $itemYearMonth = $itemDate->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth);
                $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                $prevTotalPurchases += $volumeSm3 * floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            }
        }
        
        // Menghitung saldo bulan sebelumnya
        $prevMonthBalance = $prevTotalDeposits - $prevTotalPurchases;
        
        // Menghitung saldo bulan ini
        $currentMonthBalance = $prevMonthBalance + $totalDeposit - $totalBiaya;
        
        // Menghitung biaya yang harus dibayar (jika saldo negatif)
        $biayaYangHarusDibayar = $currentMonthBalance < 0 ? abs($currentMonthBalance) : 0;

        // Setup data untuk PDF
        $data = [
            'customer' => $customer,
            'periode_bulan' => Carbon::createFromDate($tahun, $bulan, 1)->format('F Y'),
            'pemakaian_gas' => $pemakaianGas,
            'total_volume' => $totalVolume,
            'total_biaya' => $totalBiaya,
            'penerimaan_deposit' => $penerimaanDeposit,
            'total_deposit' => $totalDeposit,
            'saldo_bulan_lalu' => $prevMonthBalance,
            'sisa_saldo' => $currentMonthBalance,
            'biaya_yang_harus_dibayar' => $biayaYangHarusDibayar
        ];

        // Load view PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.billing', $data);
        
        // Set paper size to A4
        $pdf->setPaper('a4');
        
        // Return PDF untuk didownload
        return $pdf->stream('billing-' . $customer->name . '-' . $yearMonth . '.pdf');
    }