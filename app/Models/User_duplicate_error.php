<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Konstanta untuk role
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'superadmin';
    const ROLE_CUSTOMER = 'customer';
    const ROLE_FOB = 'fob';
    const ROLE_DEMO = 'demo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        // Tambahkan kolom baru
        'total_deposit',
        'total_purchases',
        'deposit_history',
        'monthly_balances',
        'harga_per_meter_kubik',
        'tekanan_keluar',
        'suhu',
        'koreksi_meter',
        'pricing_history'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'harga_per_meter_kubik' => 'decimal:2',
        'tekanan_keluar' => 'decimal:3',
        'suhu' => 'decimal:2',
        'koreksi_meter' => 'decimal:14',
        'total_deposit' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'deposit_history' => 'array',
        'monthly_balances' => 'array',
        'pricing_history' => 'array'
    ];

    /**
     * Helper function to ensure data is always an array
     */
    private function ensureArray($data)
    {
        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    public function getTotalVolumeSm3($startDate = null, $endDate = null)
    {
        $query = $this->dataPencatatan();

        // Filter berdasarkan tanggal jika diperlukan
        if ($startDate || $endDate) {
            $query = $query->whereHas('dataPencatatan', function ($q) use ($startDate, $endDate) {
                // Logika filter tanggal
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                } elseif ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
            });
        }

        // Ambil data pencatatan
        $dataPencatatan = $query->get();

        // Hitung total volume SM3
        $totalVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Gunakan koreksi meter dari pricing history jika ada
            $koreksiMeter = $this->getKoreksiMeterForDate($item->created_at);
            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;

            $totalVolumeSm3 += $volumeSm3;
        }

        return $totalVolumeSm3;
    }

    // Metode untuk menambah riwayat pricing
    public function addPricingHistory($hargaPerMeterKubik, $tekananKeluar, $suhu, $koreksiMeter, $customDate = null)
    {
        try {
            DB::beginTransaction();

            // Debug - Log input parameters
            \Log::info('addPricingHistory called', [
                'user_id' => $this->id,
                'role' => $this->role,
                'harga_input' => $hargaPerMeterKubik,
                'harga_input_type' => gettype($hargaPerMeterKubik),
                'tekanan_input' => $tekananKeluar,
                'suhu_input' => $suhu,
                'koreksi_input' => $koreksiMeter,
                'date' => $customDate ? $customDate->format('Y-m-d H:i:s') : 'now'
            ]);

            // Ensure all values are numeric and properly formatted
            $hargaPerMeterKubik = floatval(str_replace(',', '.', $hargaPerMeterKubik));
            $tekananKeluar = floatval(str_replace(',', '.', $tekananKeluar));
            $suhu = floatval(str_replace(',', '.', $suhu));
            $koreksiMeter = floatval(str_replace(',', '.', $koreksiMeter));

            // Log the converted values
            \Log::info('Converted pricing values', [
                'harga_converted' => $hargaPerMeterKubik,
                'tekanan_converted' => $tekananKeluar,
                'suhu_converted' => $suhu,
                'koreksi_converted' => $koreksiMeter
            ]);

            $pricingDate = $customDate ?: now();
            $yearMonth = $pricingDate->format('Y-m');

            // Prepare pricing entry
            $pricingEntry = [
                'date' => $pricingDate->format('Y-m-d H:i:s'),
                'year_month' => $yearMonth,
                'harga_per_meter_kubik' => round($hargaPerMeterKubik, 2),
                'tekanan_keluar' => round($tekananKeluar, 3),
                'suhu' => round($suhu, 2),
                'koreksi_meter' => round($koreksiMeter, 8)
            ];

            // Get current pricing history
            $pricingHistory = $this->ensureArray($this->pricing_history);

            // Debug - Log current pricing history
            \Log::info('Current pricing history', ['history' => $pricingHistory]);

            // Cek apakah sudah ada entri untuk bulan dan tahun yang sama
            $existingIndex = null;
            foreach ($pricingHistory as $index => $entry) {
                if (isset($entry['year_month']) && $entry['year_month'] === $yearMonth) {
                    $existingIndex = $index;
                    break;
                }
            }

            // Update jika sudah ada, tambahkan jika belum
            if ($existingIndex !== null) {
                \Log::info('Updating existing pricing entry', ['index' => $existingIndex]);
                $pricingHistory[$existingIndex] = $pricingEntry;
            } else {
                \Log::info('Adding new pricing entry');
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history - tambahkan flag untuk memastikan tidak ada perubahan format
            $this->setAttribute('pricing_history', $pricingHistory);

            // Update current values untuk bulan berjalan
            $currentMonth = now()->format('Y-m');
            if ($yearMonth === $currentMonth) {
                \Log::info('Updating current month values');
                $this->setAttribute('harga_per_meter_kubik', $hargaPerMeterKubik);
                $this->setAttribute('tekanan_keluar', $tekananKeluar);
                $this->setAttribute('suhu', $suhu);
                $this->setAttribute('koreksi_meter', $koreksiMeter);
            }

            // Debug - Log before save
            \Log::info('Before saving user', [
                'user_id' => $this->id,
                'new_pricing_history' => $this->pricing_history,
                'new_harga' => $this->harga_per_meter_kubik
            ]);

            // Save the user
            $result = $this->save();

            // Debug - Log save result
            \Log::info('Save result', [
                'result' => $result ? 'success' : 'failed',
                'user_id' => $this->id
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addPricingHistory', [
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
     * Metode untuk menambah riwayat pricing dengan periode khusus (rentang tanggal)
     */
    public function addCustomPeriodPricing($hargaPerMeterKubik, $tekananKeluar, $suhu, $koreksiMeter, $startDate, $endDate)
    {
        try {
            DB::beginTransaction();

            // Debug - Log input parameters
            \Log::info('addCustomPeriodPricing called', [
                'user_id' => $this->id,
                'role' => $this->role,
                'harga_input' => $hargaPerMeterKubik,
                'tekanan_input' => $tekananKeluar,
                'suhu_input' => $suhu,
                'koreksi_input' => $koreksiMeter,
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s')
            ]);

            // Ensure all values are numeric and properly formatted
            $hargaPerMeterKubik = floatval(str_replace(',', '.', $hargaPerMeterKubik));
            $tekananKeluar = floatval(str_replace(',', '.', $tekananKeluar));
            $suhu = floatval(str_replace(',', '.', $suhu));
            $koreksiMeter = floatval(str_replace(',', '.', $koreksiMeter));

            // Prepare custom period pricing entry
            $pricingEntry = [
                'type' => 'custom_period',
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
                'harga_per_meter_kubik' => round($hargaPerMeterKubik, 2),
                'tekanan_keluar' => round($tekananKeluar, 3),
                'suhu' => round($suhu, 2),
                'koreksi_meter' => round($koreksiMeter, 8)
            ];

            // Get current custom period pricing history
            $pricingHistory = $this->ensureArray($this->pricing_history);

            // Cari apakah sudah ada periode yang overlap dengan periode baru
            $overlappingIndex = null;
            foreach ($pricingHistory as $index => $entry) {
                if (isset($entry['type']) && $entry['type'] === 'custom_period') {
                    $entryStartDate = Carbon::parse($entry['start_date']);
                    $entryEndDate = Carbon::parse($entry['end_date']);

                    // Cek apakah rentang tanggal overlap
                    if (($startDate <= $entryEndDate) && ($endDate >= $entryStartDate)) {
                        $overlappingIndex = $index;
                        break;
                    }
                }
            }

            // Update jika sudah ada periode yang overlap, tambahkan jika belum
            if ($overlappingIndex !== null) {
                \Log::info('Updating existing custom period pricing entry', ['index' => $overlappingIndex]);
                $pricingHistory[$overlappingIndex] = $pricingEntry;
            } else {
                \Log::info('Adding new custom period pricing entry');
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history
            $this->setAttribute('pricing_history', $pricingHistory);

            // Save the user
            $result = $this->save();

            // Debug - Log save result
            \Log::info('Save result', [
                'result' => $result ? 'success' : 'failed',
                'user_id' => $this->id
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addCustomPeriodPricing', [
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
     * Metode untuk menambah riwayat pricing khusus FOB
     */
    public function addPricingHistoryfob($hargaPerMeterKubik, $customDate = null)
    {
        try {
            DB::beginTransaction();

            // Debug - Log input parameters
            \Log::info('addPricingHistoryfob called', [
                'user_id' => $this->id,
                'role' => $this->role,
                'harga_input' => $hargaPerMeterKubik,
                'harga_input_type' => gettype($hargaPerMeterKubik),
                'date' => $customDate ? $customDate->format('Y-m-d H:i:s') : 'now'
            ]);

            // Ensure all values are numeric and properly formatted
            $hargaPerMeterKubik = floatval(str_replace(',', '.', $hargaPerMeterKubik));

            // Log the converted values
            \Log::info('Converted pricing values', [
                'harga_converted' => $hargaPerMeterKubik
            ]);

            $pricingDate = $customDate ?: now();
            $yearMonth = $pricingDate->format('Y-m');

            // Prepare pricing entry
            $pricingEntry = [
                'date' => $pricingDate->format('Y-m-d H:i:s'),
                'year_month' => $yearMonth,
                'harga_per_meter_kubik' => round($hargaPerMeterKubik, 2)
            ];

            // Get current pricing history
            $pricingHistory = $this->ensureArray($this->pricing_history);

            // Debug - Log current pricing history
            \Log::info('Current pricing history', ['history' => $pricingHistory]);

            // Cek apakah sudah ada entri untuk bulan dan tahun yang sama
            $existingIndex = null;
            foreach ($pricingHistory as $index => $entry) {
                if (isset($entry['year_month']) && $entry['year_month'] === $yearMonth) {
                    $existingIndex = $index;
                    break;
                }
            }

            // Update jika sudah ada, tambahkan jika belum
            if ($existingIndex !== null) {
                \Log::info('Updating existing pricing entry', ['index' => $existingIndex]);
                $pricingHistory[$existingIndex] = $pricingEntry;
            } else {
                \Log::info('Adding new pricing entry');
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history - tambahkan flag untuk memastikan tidak ada perubahan format
            $this->setAttribute('pricing_history', $pricingHistory);

            // Update current values untuk bulan berjalan
            $currentMonth = now()->format('Y-m');
            if ($yearMonth === $currentMonth) {
                \Log::info('Updating current month values');
                $this->setAttribute('harga_per_meter_kubik', $hargaPerMeterKubik);
            }

            // Debug - Log before save
            \Log::info('Before saving user', [
                'user_id' => $this->id,
                'new_pricing_history' => $this->pricing_history,
                'new_harga' => $this->harga_per_meter_kubik
            ]);

            // Save the user
            $result = $this->save();

            // Debug - Log save result
            \Log::info('Save result', [
                'result' => $result ? 'success' : 'failed',
                'user_id' => $this->id
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addPricingHistoryfob', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    // Mendapatkan data pricing untuk bulan dan tahun tertentu
    public function getPricingForYearMonth($yearMonth, $specificDate = null)
    {
        $pricingHistory = $this->ensureArray($this->pricing_history);
        $specificDateTime = null;

        // Khusus untuk FOB, hanya perlu harga per meter kubik, bukan koreksi meter
        $isFOB = $this->isFOB();

        if ($specificDate) {
            // Jika ada tanggal spesifik, konversi ke objek Carbon
            $specificDateTime = $specificDate instanceof Carbon ? $specificDate : Carbon::parse($specificDate);

            // Log untuk debugging
            \Log::debug('getPricingForYearMonth dengan tanggal spesifik', [
                'user_id' => $this->id,
                'yearMonth' => $yearMonth,
                'specificDate' => $specificDateTime->format('Y-m-d H:i:s'),
                'pricingHistory_count' => count($pricingHistory),
                'is_fob' => $isFOB
            ]);
        }

        // Periksa terlebih dahulu apakah ada periode khusus yang mencakup tanggal spesifik
        if ($specificDateTime) {
            foreach ($pricingHistory as $entry) {
                if (isset($entry['type']) && $entry['type'] === 'custom_period') {
                    $startDate = Carbon::parse($entry['start_date']);
                    $endDate = Carbon::parse($entry['end_date']);

                    // Jika tanggal spesifik berada dalam rentang periode khusus
                    if ($specificDateTime->between($startDate, $endDate)) {
                        \Log::debug('Menemukan periode khusus yang cocok', [
                            'start_date' => $startDate->format('Y-m-d'),
                            'end_date' => $endDate->format('Y-m-d'),
                            'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'],
                            'koreksi_meter' => $entry['koreksi_meter'] ?? 'N/A',
                            'is_fob' => $isFOB
                        ]);

                        // Jika FOB, pastikan nilai koreksi_meter selalu 1.0
                        if ($isFOB) {
                            $result = [
                                'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik,
                                'koreksi_meter' => 1.0,
                                'is_fob' => true
                            ];
                            if (isset($entry['type'])) $result['type'] = $entry['type'];
                            return $result;
                        }

                        return $entry;
                    }
                }
            }
        }

        // Jika tidak ada periode khusus yang cocok, cari berdasarkan year_month
        foreach ($pricingHistory as $entry) {
            if (isset($entry['year_month']) && $entry['year_month'] === $yearMonth) {
                \Log::debug('Menemukan pricing bulanan yang cocok', [
                    'year_month' => $yearMonth,
                    'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'],
                    'koreksi_meter' => $entry['koreksi_meter'] ?? 'N/A',
                    'is_fob' => $isFOB
                ]);

                // Jika FOB, pastikan nilai koreksi_meter selalu 1.0
                if ($isFOB) {
                    $result = [
                        'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik,
                        'koreksi_meter' => 1.0,
                        'is_fob' => true
                    ];
                    if (isset($entry['type'])) $result['type'] = $entry['type'];
                    return $result;
                }

                return $entry;
            }
        }

        // Jika tidak ditemukan, kembalikan data pricing terakhir
        if ($isFOB) {
            // Untuk FOB, hanya return harga per meter kubik
            $defaultPricing = [
                'harga_per_meter_kubik' => $this->harga_per_meter_kubik,
                'koreksi_meter' => 1.0,
                'is_fob' => true
            ];
        } else {
            // Untuk customer reguler, return semua nilai
            $defaultPricing = [
                'harga_per_meter_kubik' => $this->harga_per_meter_kubik,
                'tekanan_keluar' => $this->tekanan_keluar,
                'suhu' => $this->suhu,
                'koreksi_meter' => $this->koreksi_meter
            ];
        }

        \Log::debug('Menggunakan default pricing', [
            'year_month' => $yearMonth,
            'harga_per_meter_kubik' => $defaultPricing['harga_per_meter_kubik'],
            'koreksi_meter' => $defaultPricing['koreksi_meter'],
            'is_fob' => $isFOB
        ]);

        return $defaultPricing;
    }

    // Mendapatkan koreksi meter untuk tanggal tertentu
    public function getKoreksiMeterForDate($date)
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $yearMonth = $carbonDate->format('Y-m');

        $pricingData = $this->getPricingForYearMonth($yearMonth, $carbonDate);
        return floatval($pricingData['koreksi_meter'] ?? $this->koreksi_meter);
    }

    // Mendapatkan harga per meter kubik untuk tanggal tertentu
    public function getHargaPerMeterKubikForDate($date)
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $yearMonth = $carbonDate->format('Y-m');

        $pricingData = $this->getPricingForYearMonth($yearMonth, $carbonDate);
        return floatval($pricingData['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik);
    }

    /**
     * Relationship with Data Pencatatan
     */
    public function dataPencatatan()
    {
        return $this->hasMany(DataPencatatan::class, 'customer_id');
    }

    /**
     * Reduce balance (pengurangan saldo)
     *
     * @param float $amount Amount to reduce
     * @param string|null $description Description for the reduction
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function reduceBalance($amount, $description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            $depositDate = $customDate ? $customDate : now();

            // Ensure amount is numeric and negative for reduction
            $amount = -abs(floatval($amount));

            // Prepare deposit entry dengan keterangan pengurangan
            $depositEntry = [
                'date' => $depositDate->format('Y-m-d H:i:s'),
                'amount' => round($amount, 2),
                'keterangan' => 'pengurangan',
                'deskripsi' => $description
            ];

            // Get current deposit history and ensure it's an array
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Add new entry to history
            $depositHistory[] = $depositEntry;

            // Update user's total deposit (dikurangi karena amount sudah negatif)
            $this->total_deposit += $amount;

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // Update ALL monthly balances untuk memastikan konsistensi saldo
            // DISABLED TEMPORARILY to prevent performance issues
            // $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            // $this->updateMonthlyBalances($threeYearsAgo);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Zero balance (nol-kan saldo)
     *
     * @param string|null $description Description for zeroing balance
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function zeroBalance($description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            
            // Get current balance
            $currentBalance = $this->getCurrentBalance();
            
            // If balance is already zero, do nothing
            if (abs($currentBalance) < 0.01) {
                return true;
            }
            
            // Create reduction entry to zero the balance
            $reductionAmount = -$currentBalance; // Amount needed to make balance zero
            
            $result = $this->reduceBalance(
                abs($reductionAmount), 
                $description ?? 'Nol-kan saldo', 
                $customDate
            );
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Get deposit history with backward compatibility
     * Automatically adds 'keterangan' field to old entries
     */
    public function getDepositHistoryWithKeterangan()
    {
        $depositHistory = $this->ensureArray($this->deposit_history);
        
        // Add backward compatibility for old entries
        foreach ($depositHistory as &$entry) {
            // If keterangan doesn't exist, assume it's 'penambahan'
            if (!isset($entry['keterangan'])) {
                $entry['keterangan'] = 'penambahan';
            }
            
            // Handle description field rename
            if (isset($entry['description']) && !isset($entry['deskripsi'])) {
                $entry['deskripsi'] = $entry['description'];
            }
        }
        
        return $depositHistory;
    }

    /**
     * Reduce balance (pengurangan saldo)
     *
     * @param float $amount Amount to reduce
     * @param string|null $description Description for the reduction
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function reduceBalance($amount, $description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            $depositDate = $customDate ? $customDate : now();

            // Ensure amount is numeric and negative for reduction
            $amount = -abs(floatval($amount));

            // Prepare deposit entry dengan keterangan pengurangan
            $depositEntry = [
                'date' => $depositDate->format('Y-m-d H:i:s'),
                'amount' => round($amount, 2),
                'keterangan' => 'pengurangan',
                'deskripsi' => $description
            ];

            // Get current deposit history and ensure it's an array
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Add new entry to history
            $depositHistory[] = $depositEntry;

            // Update user's total deposit (dikurangi karena amount sudah negatif)
            $this->total_deposit += $amount;

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // Update ALL monthly balances untuk memastikan konsistensi saldo
            // DISABLED TEMPORARILY to prevent performance issues  
            // $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            // $this->updateMonthlyBalances($threeYearsAgo);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Zero balance (nol-kan saldo)
     *
     * @param string|null $description Description for zeroing balance
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function zeroBalance($description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            
            // Get current balance
            $currentBalance = $this->getCurrentBalance();
            
            // If balance is already zero, do nothing
            if (abs($currentBalance) < 0.01) {
                return true;
            }
            
            // Create reduction entry to zero the balance
            $reductionAmount = -$currentBalance; // Amount needed to make balance zero
            
            $result = $this->reduceBalance(
                abs($reductionAmount), 
                $description ?? 'Nol-kan saldo', 
                $customDate
            );
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Get deposit history with backward compatibility
     * Automatically adds 'keterangan' field to old entries
     */
    public function getDepositHistoryWithKeterangan()
    {
        $depositHistory = $this->ensureArray($this->deposit_history);
        
        // Add backward compatibility for old entries
        foreach ($depositHistory as &$entry) {
            // If keterangan doesn't exist, assume it's 'penambahan'
            if (!isset($entry['keterangan'])) {
                $entry['keterangan'] = 'penambahan';
            }
            
            // Handle description field rename
            if (isset($entry['description']) && !isset($entry['deskripsi'])) {
                $entry['deskripsi'] = $entry['description'];
            }
        }
        
        return $depositHistory;
    }

    /**
     * Add deposit with optional custom date
     *
     * @param float $amount Deposit amount
     * @param string|null $description Deposit description
     * @param Carbon|null $customDate Custom deposit date (optional)
     * @param string $keterangan Type of transaction (penambahan/pengurangan)
     * @return bool
     */
    public function addDeposit($amount, $description = null, $customDate = null, $keterangan = 'penambahan')
    {
        try {
            DB::beginTransaction();
            $depositDate = $customDate ? $customDate : now();

            // Ensure amount is numeric
            $amount = floatval($amount);

            // Prepare deposit entry dengan struktur baru
            $depositEntry = [
                'date' => $depositDate->format('Y-m-d H:i:s'),
                'amount' => round($amount, 2),
                'keterangan' => $keterangan,
                'deskripsi' => $description
            ];

            // Get current deposit history and ensure it's an array
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Add new deposit to history
            $depositHistory[] = $depositEntry;

            // Update user's total deposit
            $this->total_deposit += $amount;

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // Update ALL monthly balances untuk memastikan konsistensi saldo
            // Mulai dari 3 tahun lalu untuk memastikan semua data tercakup
            // DISABLED TEMPORARILY to prevent performance issues
            // $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            // $this->updateMonthlyBalances($threeYearsAgo);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function removeDeposit($index)
    {
        try {
            DB::beginTransaction();

            // Get current deposit history
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Validate index
            if (!isset($depositHistory[$index])) {
                return false;
            }

            // Capture deposit date and amount before removing it
            $depositDate = null;
            $depositAmount = floatval($depositHistory[$index]['amount'] ?? 0);
            if (isset($depositHistory[$index]['date'])) {
                $depositDate = Carbon::parse($depositHistory[$index]['date']);
            }

            // Subtract the amount from total deposit
            $this->total_deposit -= $depositAmount;

            // Remove the specific deposit entry
            array_splice($depositHistory, $index, 1);

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // Update ALL monthly balances untuk memastikan konsistensi saldo
            // DISABLED TEMPORARILY to prevent performance issues
            // $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            // $this->updateMonthlyBalances($threeYearsAgo);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function recordPurchase($amount)
    {
        try {
            DB::beginTransaction();

            // Ensure amount is numeric
            $amount = floatval($amount);

            // Update total purchases
            $this->total_purchases += $amount;

            // Save the user
            $this->save();

            // Update ALL monthly balances untuk memastikan konsistensi saldo
            // DISABLED TEMPORARILY to prevent performance issues
            // $threeYearsAgo = Carbon::now()->subYears(3)->format('Y-m');
            // $this->updateMonthlyBalances($threeYearsAgo);

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
