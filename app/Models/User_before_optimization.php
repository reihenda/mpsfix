            $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            $this->updateMonthlyBalances($threeYearsAgo);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getCurrentBalance()
    {
        return floatval($this->total_deposit) - floatval($this->total_purchases);
    }

    /**
     * Update saldo bulanan pengguna untuk seluruh periode
     * Memastikan kontinuitas saldo dari bulan ke bulan, bahkan jika tidak ada aktivitas
     *
     * @param string|null $startMonth Format Y-m, bulan awal untuk memulai perhitungan
     * @return bool
     */
    public function updateMonthlyBalances($startMonth = null)
    {
        try {
            DB::beginTransaction();

            // Log awal proses update
            \Log::info('Memulai updateMonthlyBalances', [
                'user_id' => $this->id,
                'name' => $this->name,
                'role' => $this->role,
                'startMonth' => $startMonth
            ]);

            // PERBAIKAN: Mulai dari 4 tahun yang lalu untuk memastikan semua data historis tercakup
            // dengan lebih baik terutama untuk customer FOB dengan riwayat panjang
            $absoluteStartMonth = Carbon::now()->subYears(4)->startOfMonth()->format('Y-m');

            // Jika startMonth disediakan, gunakan yang lebih awal antara absoluteStartMonth dan startMonth
            if ($startMonth) {
                $startMonthDate = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
                $absoluteStartDate = Carbon::createFromFormat('Y-m', $absoluteStartMonth)->startOfMonth();

                if ($absoluteStartDate->lt($startMonthDate)) {
                    $startMonth = $absoluteStartMonth;
                }
            } else {
                $startMonth = $absoluteStartMonth;
            }

            // Ambil saldo bulanan yang sudah ada atau inisialisasi array kosong
            $monthlyBalances = $this->ensureArray($this->monthly_balances);

            // Ambil semua deposit
            $deposits = $this->ensureArray($this->deposit_history);

            // Ambil semua data pencatatan - PERBAIKAN: Ambil dengan benar
            $records = $this->dataPencatatan()->get();

            // Log jumlah data
            \Log::info('Jumlah data untuk perhitungan saldo bulanan', [
                'user_id' => $this->id,
                'deposits_count' => count($deposits),
                'records_count' => $records->count()
            ]);

            // Periksa apakah ada data sebelum startMonth untuk menentukan saldo awal
            $startDate = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();

            // PERBAIKAN: Hitung hingga 12 bulan ke depan, bukan hanya 6 bulan
            $endDate = Carbon::now()->addMonths(12)->endOfMonth();

            // 1. Siapkan array untuk klasifikasi semua deposit dan pembelian per bulan
            $monthlyDeposits = [];
            $monthlyPurchases = [];

            // 2. Tentukan rentang waktu lengkap
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $currentYearMonth = $currentDate->format('Y-m');
                $monthlyDeposits[$currentYearMonth] = 0;
                $monthlyPurchases[$currentYearMonth] = 0;
                $currentDate->addMonth();
            }

            // 3. PERBAIKAN: Klasifikasikan semua deposit dengan logging lebih detail
            $depositsByMonth = []; // untuk debugging
            foreach ($deposits as $index => $deposit) {
                if (isset($deposit['date'])) {
                    try {
                        $depositDate = Carbon::parse($deposit['date']);
                        $depositAmount = floatval($deposit['amount'] ?? 0);
                        $depositYearMonth = $depositDate->format('Y-m');

                        // Jika bulan deposit tercakup dalam range yang kita proses
                        if (isset($monthlyDeposits[$depositYearMonth])) {
                            $monthlyDeposits[$depositYearMonth] += $depositAmount;

                            // Tambahkan ke array untuk debugging
                            if (!isset($depositsByMonth[$depositYearMonth])) {
                                $depositsByMonth[$depositYearMonth] = [];
                            }
                            $depositsByMonth[$depositYearMonth][] = [
                                'index' => $index,
                                'date' => $depositDate->format('Y-m-d H:i:s'),
                                'amount' => $depositAmount
                            ];
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error parsing deposit date: ' . $e->getMessage(), [
                            'deposit_index' => $index,
                            'deposit' => $deposit,
                            'user_id' => $this->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Log deposit details untuk debugging
            \Log::info('Deposit classification', [
                'user_id' => $this->id,
                'role' => $this->role,
                'deposits_by_month' => $depositsByMonth
            ]);

            // 4. PERBAIKAN: Klasifikasikan semua pembelian dengan logging lebih detail
            $purchasesByMonth = []; // untuk debugging
            foreach ($records as $recordIndex => $record) {
                try {
                    $dataInput = $this->ensureArray($record->data_input);
                    $recordDate = null;

                    // PERBAIKAN: Prioritaskan format tanggal sesuai jenis customer (FOB atau reguler)
                    if ($this->isFOB()) {
                        // FOB: prioritaskan format 'waktu' yang digunakan di data FOB
                        if (!empty($dataInput['waktu'])) {
                            $recordDate = Carbon::parse($dataInput['waktu']);
                        } elseif (!empty($dataInput['pembacaan_awal']['waktu'])) {
                            $recordDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                        } elseif ($record->created_at) {
                            $recordDate = Carbon::parse($record->created_at);
                        } else {
                            continue; // Skip record tanpa tanggal
                        }
                    } else {
                        // Customer reguler: prioritaskan 'pembacaan_awal.waktu'
                        if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                            $recordDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                        } elseif (!empty($dataInput['waktu'])) {
                            $recordDate = Carbon::parse($dataInput['waktu']);
                        } elseif ($record->created_at) {
                            $recordDate = Carbon::parse($record->created_at);
                        } else {
                            continue; // Skip record tanpa tanggal
                        }
                    }

                    $recordYearMonth = $recordDate->format('Y-m');

                    // Jika bulan pencatatan tercakup dalam range yang kita proses
                    if (isset($monthlyPurchases[$recordYearMonth])) {
                        // Gunakan harga_final jika tersedia (lebih akurat)
                        if ($record->harga_final > 0) {
                            $purchaseAmount = floatval($record->harga_final);
                            $monthlyPurchases[$recordYearMonth] += $purchaseAmount;

                            // Tambahkan ke array untuk debugging
                            if (!isset($purchasesByMonth[$recordYearMonth])) {
                                $purchasesByMonth[$recordYearMonth] = [];
                            }
                            $purchasesByMonth[$recordYearMonth][] = [
                                'id' => $record->id,
                                'date' => $recordDate->format('Y-m-d H:i:s'),
                                'harga_final' => $purchaseAmount,
                                'source' => 'harga_final'
                            ];
                        } else {
                            // Jika tidak ada harga_final, hitung berdasarkan volume dan harga
                            $volumeSm3 = 0;
                            $source = '';

                            // FOB: volume_sm3
                            if (isset($dataInput['volume_sm3'])) {
                                $volumeSm3 = floatval($dataInput['volume_sm3']);
                                $source = 'volume_sm3';
                            }
                            // Customer reguler: volume_flow_meter * koreksi
                            elseif (isset($dataInput['volume_flow_meter'])) {
                                $volumeFlowMeter = floatval($dataInput['volume_flow_meter']);
                                $pricingInfo = $this->getPricingForYearMonth($recordYearMonth, $recordDate);
                                $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $this->koreksi_meter);
                                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                                $source = 'volume_flow_meter';
                            }

                            if ($volumeSm3 > 0) {
                                $pricingInfo = $this->getPricingForYearMonth($recordYearMonth, $recordDate);
                                $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik);
                                $calculatedPrice = $volumeSm3 * $hargaPerM3;
                                $monthlyPurchases[$recordYearMonth] += $calculatedPrice;

                                // Tambahkan ke array untuk debugging
                                if (!isset($purchasesByMonth[$recordYearMonth])) {
                                    $purchasesByMonth[$recordYearMonth] = [];
                                }
                                $purchasesByMonth[$recordYearMonth][] = [
                                    'id' => $record->id,
                                    'date' => $recordDate->format('Y-m-d H:i:s'),
                                    'volume' => $volumeSm3,
                                    'harga_per_m3' => $hargaPerM3,
                                    'calculated_price' => $calculatedPrice,
                                    'source' => $source
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error processing record: ' . $e->getMessage(), [
                        'record_id' => $record->id,
                        'user_id' => $this->id,
                        'role' => $this->role,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Log purchase details untuk debugging
            \Log::info('Purchase classification', [
                'user_id' => $this->id,
                'role' => $this->role,
                'purchases_by_month' => $purchasesByMonth
            ]);

            // 5. Log untuk debugging
            \Log::info('Klasifikasi deposit dan pembelian bulanan selesai', [
                'user_id' => $this->id,
                'role' => $this->role,
                'deposits_count' => count(array_filter($monthlyDeposits)),
                'purchases_count' => count(array_filter($monthlyPurchases)),
                'months_range' => array_keys($monthlyDeposits)
            ]);

            // 6. PERBAIKAN: Hitung saldo berjalan untuk setiap bulan dengan validasi
            // Mulai dari 0 tetapi akumulasi dari bulan paling awal
            $runningBalance = 0;
            $currentDate = $startDate->copy();

            // Debug untuk bulan-bulan dengan saldo: pastikan konsisten
            $monthsWithBalance = [];
            $monthDetails = [];

            while ($currentDate <= $endDate) {
                $currentYearMonth = $currentDate->format('Y-m');

                // Ambil deposit dan pembelian bulan ini
                $monthDeposit = $monthlyDeposits[$currentYearMonth] ?? 0;
                $monthPurchase = $monthlyPurchases[$currentYearMonth] ?? 0;

                // Hitung saldo akhir bulan ini: saldo awal + deposit - pembelian
                $runningBalance += $monthDeposit - $monthPurchase;

                // Simpan saldo bulan ini dengan pembulatan yang konsisten
                $monthlyBalances[$currentYearMonth] = round($runningBalance, 2);
                $monthsWithBalance[] = $currentYearMonth;

                // Simpan detail bulan untuk logging
                $monthDetails[$currentYearMonth] = [
                    'beginning_balance' => round($runningBalance - ($monthDeposit - $monthPurchase), 2),
                    'deposits' => $monthDeposit,
                    'purchases' => $monthPurchase,
                    'ending_balance' => round($runningBalance, 2)
                ];

                // Pindah ke bulan berikutnya
                $currentDate->addMonth();
            }

            // Log detail semua bulan untuk debugging
            \Log::info('Monthly balance details', [
                'user_id' => $this->id,
                'role' => $this->role,
                'month_details' => $monthDetails
            ]);

            // 7. Double-check konsistensi dengan total saldo
            $totalCalculatedBalance = $runningBalance; // Saldo akhir dari perhitungan bulanan
            $actualTotalBalance = $this->getCurrentBalance(); // Saldo dari total_deposit - total_purchases

            // Log perbandingan untuk debugging
            \Log::info('Perbandingan saldo total vs saldo bulanan terakhir', [
                'user_id' => $this->id,
                'role' => $this->role,
                'total_deposits' => $this->total_deposit,
                'total_purchases' => $this->total_purchases,
                'total_calculated_balance' => $totalCalculatedBalance,
                'actual_total_balance' => $actualTotalBalance,
                'difference' => $actualTotalBalance - $totalCalculatedBalance,
                'months_with_balance' => $monthsWithBalance
            ]);

            // PERBAIKAN: Jika perbedaan signifikan (> 0.01), sesuaikan saldo bulan terakhir
            if (abs($totalCalculatedBalance - $actualTotalBalance) > 0.01) {
                \Log::warning('Perbedaan signifikan antara saldo total dan saldo bulanan', [
                    'user_id' => $this->id,
                    'role' => $this->role,
                    'total_calculated_balance' => $totalCalculatedBalance,
                    'actual_total_balance' => $actualTotalBalance,
                    'difference' => $actualTotalBalance - $totalCalculatedBalance
                ]);

                // Jika ada bulan-bulan dengan saldo, sesuaikan bulan terakhir
                if (!empty($monthsWithBalance)) {
                    // Ambil bulan terakhir
                    $lastMonth = end($monthsWithBalance);
                    // Sesuaikan saldo bulan terakhir agar cocok dengan saldo total
                    $adjustment = $actualTotalBalance - $totalCalculatedBalance;
                    $originalBalance = $monthlyBalances[$lastMonth] ?? 0;
                    $monthlyBalances[$lastMonth] = round($originalBalance + $adjustment, 2);

                    \Log::info('Menyesuaikan saldo bulan terakhir', [
                        'user_id' => $this->id,
                        'role' => $this->role,
                        'last_month' => $lastMonth,
                        'original_balance' => $originalBalance,
                        'adjusted_balance' => $monthlyBalances[$lastMonth],
                        'adjustment' => $adjustment
                    ]);
                }
            }

            // 8. Simpan saldo bulanan yang sudah diupdate
            $this->monthly_balances = $monthlyBalances;
            $result = $this->save();

            DB::commit();
            \Log::info('Berhasil update monthly_balances dengan presisi tinggi', [
                'user_id' => $this->id,
                'role' => $this->role,
                'balance_entries' => count($monthlyBalances),
                'success' => $result
            ]);
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in updateMonthlyBalances: ' . $e->getMessage(), [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is customer
     */
    public function isCustomer()
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /**
     * Check if user is customer or FOB
     * Fungsi untuk bisa menampilkan data FOB di dashboard
     */
    public function isCustomerOrFOB()
    {
        return $this->role === self::ROLE_CUSTOMER || $this->role === self::ROLE_FOB;
    }

    /**
     * Check if user is demo
     */
    public function isDemo()
    {
        return $this->role === self::ROLE_DEMO;
    }

    /**
     * Check if user is FOB
     */
    public function isFOB()
    {
        return $this->role === self::ROLE_FOB;
    }
}
