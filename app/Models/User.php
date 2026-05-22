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
    const ROLE_KEUANGAN = 'keuangan';
    const ROLE_CUSTOMER = 'customer';
    const ROLE_FOB = 'fob';
    const ROLE_DEMO = 'demo';
    const ROLE_STAFF = 'staff';
    const ROLE_MMBTU = 'mmbtu';

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
        'no_kontrak',
        'alamat',
        'nomor_tlpn',
        // Tambahkan kolom baru
        'total_deposit',
        'total_purchases',
        'deposit_history',
        'monthly_balances',
        'harga_per_meter_kubik',
        'harga_per_mmbtu_usd',
        'pembagi_sm3_ke_mmbtu',
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
        'harga_per_mmbtu_usd' => 'decimal:4',
        'pembagi_sm3_ke_mmbtu' => 'decimal:6',
        'tekanan_keluar' => 'decimal:3',
        'suhu' => 'decimal:2',
        'koreksi_meter' => 'decimal:14',
        'total_deposit' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'deposit_history' => 'array',
        'monthly_balances' => 'array',
        'pricing_history' => 'array',
        'balance_last_updated_at' => 'datetime',
        'use_realtime_calculation' => 'boolean'
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

            // Ensure all values are numeric and properly formatted
            $hargaPerMeterKubik = floatval(str_replace(',', '.', $hargaPerMeterKubik));
            $tekananKeluar = floatval(str_replace(',', '.', $tekananKeluar));
            $suhu = floatval(str_replace(',', '.', $suhu));
            $koreksiMeter = floatval(str_replace(',', '.', $koreksiMeter));

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
                $pricingHistory[$existingIndex] = $pricingEntry;
            } else {
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history
            $this->setAttribute('pricing_history', $pricingHistory);

            // Update current values untuk bulan berjalan
            $currentMonth = now()->format('Y-m');
            if ($yearMonth === $currentMonth) {
                $this->setAttribute('harga_per_meter_kubik', $hargaPerMeterKubik);
                $this->setAttribute('tekanan_keluar', $tekananKeluar);
                $this->setAttribute('suhu', $suhu);
                $this->setAttribute('koreksi_meter', $koreksiMeter);
            }

            // Save the user
            $result = $this->save();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addPricingHistory', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
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
                $pricingHistory[$overlappingIndex] = $pricingEntry;
            } else {
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history
            $this->setAttribute('pricing_history', $pricingHistory);

            // Save the user
            $result = $this->save();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addCustomPeriodPricing', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
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

            // Ensure all values are numeric and properly formatted
            $hargaPerMeterKubik = floatval(str_replace(',', '.', $hargaPerMeterKubik));

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
                $pricingHistory[$existingIndex] = $pricingEntry;
            } else {
                $pricingHistory[] = $pricingEntry;
            }

            // Update pricing history
            $this->setAttribute('pricing_history', $pricingHistory);

            // Update current values untuk bulan berjalan
            $currentMonth = now()->format('Y-m');
            if ($yearMonth === $currentMonth) {
                $this->setAttribute('harga_per_meter_kubik', $hargaPerMeterKubik);
            }

            // Save the user
            $result = $this->save();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addPricingHistoryfob', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
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
        }

        // Periksa terlebih dahulu apakah ada periode khusus yang mencakup tanggal spesifik
        if ($specificDateTime) {
            foreach ($pricingHistory as $entry) {
                if (isset($entry['type']) && $entry['type'] === 'custom_period') {
                    $startDate = Carbon::parse($entry['start_date']);
                    $endDate = Carbon::parse($entry['end_date']);

                    // Jika tanggal spesifik berada dalam rentang periode khusus
                    if ($specificDateTime->between($startDate, $endDate)) {
                        // Jika FOB, pastikan nilai koreksi_meter selalu 1.0
                        if ($isFOB) {
                            $result = [
                                'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'] ?? 0,
                                'koreksi_meter' => 1.0,
                                'is_fob' => true,
                                'is_price_set' => true
                            ];
                            if (isset($entry['type'])) $result['type'] = $entry['type'];
                            return $result;
                        }

                        $entry['is_price_set'] = true;
                        return $entry;
                    }
                }
            }
        }

        // Jika tidak ada periode khusus yang cocok, cari berdasarkan year_month
        foreach ($pricingHistory as $entry) {
            if (isset($entry['year_month']) && $entry['year_month'] === $yearMonth) {
                // Jika FOB, pastikan nilai koreksi_meter selalu 1.0
                if ($isFOB) {
                    $result = [
                        'harga_per_meter_kubik' => $entry['harga_per_meter_kubik'] ?? 0,
                        'koreksi_meter' => 1.0,
                        'is_fob' => true,
                        'is_price_set' => true
                    ];
                    if (isset($entry['type'])) $result['type'] = $entry['type'];
                    return $result;
                }

                $entry['is_price_set'] = true;
                return $entry;
            }
        }

        // Jika tidak ditemukan, kembalikan Rp.0 sebagai default (harga belum diinput)
        if ($isFOB) {
            // Untuk FOB, return harga 0
            $defaultPricing = [
                'harga_per_meter_kubik' => 0,
                'koreksi_meter' => 1.0,
                'is_fob' => true,
                'is_price_set' => false
            ];
        } else {
            // Untuk customer reguler, return semua nilai dengan harga 0
            $defaultPricing = [
                'harga_per_meter_kubik' => 0,
                'tekanan_keluar' => 0,
                'suhu' => 0,
                'koreksi_meter' => 1,
                'is_price_set' => false
            ];
        }

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
        return floatval($pricingData['harga_per_meter_kubik'] ?? 0);
    }

    /**
     * Mendapatkan daftar bulan yang memiliki data pencatatan tapi belum ada harganya
     *
     * @return array Array berisi bulan-bulan yang belum diinput harganya
     */
    public function getMonthsWithoutPricing()
    {
        $monthsWithoutPricing = [];
        $monthsWithData = [];

        // Untuk FOB, gunakan rekapPengambilan
        if ($this->isFOB()) {
            $rekapPengambilans = $this->rekapPengambilan()->get();

            foreach ($rekapPengambilans as $rekap) {
                if (!empty($rekap->tanggal_pengambilan)) {
                    $waktu = Carbon::parse($rekap->tanggal_pengambilan);
                    $yearMonth = $waktu->format('Y-m');

                    if (!in_array($yearMonth, $monthsWithData)) {
                        $monthsWithData[] = $yearMonth;
                    }
                }
            }
        } else {
            // Untuk customer reguler, gunakan dataPencatatan
            $dataPencatatans = $this->dataPencatatan()->get();

            foreach ($dataPencatatans as $data) {
                $dataInput = is_string($data->data_input)
                    ? json_decode($data->data_input, true)
                    : $data->data_input;

                if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    $waktu = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                    $yearMonth = $waktu->format('Y-m');

                    if (!in_array($yearMonth, $monthsWithData)) {
                        $monthsWithData[] = $yearMonth;
                    }
                }
            }
        }

        // Cek setiap bulan apakah sudah ada harganya
        foreach ($monthsWithData as $yearMonth) {
            $pricingInfo = $this->getPricingForYearMonth($yearMonth);

            // Jika is_price_set = false, berarti bulan ini belum diinput harganya
            if (!($pricingInfo['is_price_set'] ?? false)) {
                // Parse year-month untuk tampilan
                $carbonDate = Carbon::createFromFormat('Y-m', $yearMonth);
                $monthsWithoutPricing[] = [
                    'year_month' => $yearMonth,
                    'year' => $carbonDate->year,
                    'month' => $carbonDate->month,
                    'month_name' => $carbonDate->translatedFormat('F'),
                    'display' => $carbonDate->translatedFormat('F Y'),
                    'customer_id' => $this->id,
                    'customer_name' => $this->name
                ];
            }
        }

        // Urutkan berdasarkan year_month (terlama ke terbaru)
        usort($monthsWithoutPricing, function($a, $b) {
            return strcmp($a['year_month'], $b['year_month']);
        });

        return $monthsWithoutPricing;
    }

    /**
     * Mendapatkan jumlah bulan yang belum diinput harganya
     *
     * @return int
     */
    public function countMonthsWithoutPricing()
    {
        return count($this->getMonthsWithoutPricing());
    }

    /**
     * Relationship with Data Pencatatan
     */
    public function dataPencatatan()
    {
        return $this->hasMany(DataPencatatan::class, 'customer_id');
    }
    
    /**
     * Relationship with Rekap Pengambilan
     */
    public function rekapPengambilan()
    {
        return $this->hasMany(RekapPengambilan::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'customer_id');
    }

    // REMOVED: Relationships with complex balance tables (Pure MVC approach)
    // public function monthlyBalances() - Now using JSON field in users table
    // public function transactionCalculations() - Now calculated on-the-fly

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

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addDeposit', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in reduceBalance', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
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
            \Log::error('Error in zeroBalance', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
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

            // Capture deposit amount before removing it
            $depositAmount = floatval($depositHistory[$index]['amount'] ?? 0);

            // Subtract the amount from total deposit
            $this->total_deposit -= $depositAmount;

            // Remove the specific deposit entry
            array_splice($depositHistory, $index, 1);

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in removeDeposit', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Edit deposit entry
     *
     * @param int $index Index deposit yang akan diedit
     * @param float $newAmount Jumlah deposit baru
     * @param string|null $newDescription Deskripsi baru
     * @param Carbon|null $newDate Tanggal baru
     * @param string|null $newKeterangan Keterangan baru (penambahan/pengurangan)
     * @return bool
     */
    public function editDeposit($index, $newAmount, $newDescription = null, $newDate = null, $newKeterangan = null)
    {
        try {
            DB::beginTransaction();

            // Get current deposit history
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Validate index
            if (!isset($depositHistory[$index])) {
                \Log::error('Invalid deposit index', [
                    'user_id' => $this->id,
                    'index' => $index
                ]);
                return false;
            }

            // Get old deposit data
            $oldDeposit = $depositHistory[$index];
            $oldAmount = floatval($oldDeposit['amount'] ?? 0);

            // Ensure new amount is numeric
            $newAmount = floatval($newAmount);

            // Update total_deposit: subtract old amount, add new amount
            $this->total_deposit = $this->total_deposit - $oldAmount + $newAmount;

            // Prepare updated deposit entry
            $updatedEntry = [
                'date' => $newDate ? $newDate->format('Y-m-d H:i:s') : ($oldDeposit['date'] ?? now()->format('Y-m-d H:i:s')),
                'amount' => round($newAmount, 2),
                'keterangan' => $newKeterangan ?? ($oldDeposit['keterangan'] ?? 'penambahan'),
                'deskripsi' => $newDescription ?? ($oldDeposit['deskripsi'] ?? ($oldDeposit['description'] ?? null))
            ];

            // Update the specific deposit entry
            $depositHistory[$index] = $updatedEntry;

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            DB::commit();

            \Log::info('Deposit edited successfully', [
                'user_id' => $this->id,
                'index' => $index,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in editDeposit', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
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

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in recordPurchase', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getCurrentBalance()
    {
        return floatval($this->total_deposit) - floatval($this->total_purchases);
    }

    /**
     * ==================================================
     * PURE MVC BALANCE CALCULATION METHODS
     * ==================================================
     */

    /**
     * REAL-TIME BALANCE CALCULATION (Pure Model Method)
     */
    public function calculateRealTimeBalance($yearMonth = null)
    {
        $yearMonth = $yearMonth ?? now()->format('Y-m');
        
        // Calculate total deposits up to period
        $totalDeposits = $this->calculateTotalDepositsUntil($yearMonth);
        
        // Calculate total purchases up to period  
        $totalPurchases = $this->calculateTotalPurchasesUntil($yearMonth);
        
        return $totalDeposits - $totalPurchases;
    }

    /**
     * AUTO-UPDATE BALANCE WHEN DATA CHANGES (Model Events)
     * TEMPORARILY DISABLED - Causing infinite loop
     */
    /*
    protected static function boot()
    {
        parent::boot();
        
        static::updated(function ($user) {
            // Prevent infinite loop by checking if we're already in a balance update
            if ($user->isDirty(['deposit_history', 'pricing_history', 'total_deposit']) && 
                !$user->getAttribute('_updating_balance')) {
                $user->refreshTotalBalancesQuietly();
            }
        });
    }
    */

    /**
     * REFRESH ALL BALANCE CALCULATIONS (Core Balance Logic)
     * FIXED: Use updateQuietly to prevent infinite loop
     */
    public function refreshTotalBalances()
    {
        return $this->refreshTotalBalancesQuietly();
    }

    /**
     * REFRESH BALANCE WITHOUT TRIGGERING MODEL EVENTS
     */
    private function refreshTotalBalancesQuietly()
    {
        try {
            DB::beginTransaction();
            
            // Set flag to prevent infinite loop
            $this->setAttribute('_updating_balance', true);
            
            // Recalculate total_deposits from deposit_history
            $depositHistory = $this->ensureArray($this->deposit_history);
            $calculatedTotalDeposit = 0;
            
            foreach ($depositHistory as $deposit) {
                $amount = floatval($deposit['amount'] ?? 0);
                $keterangan = $deposit['keterangan'] ?? 'penambahan';
                
                if ($keterangan === 'pengurangan') {
                    $calculatedTotalDeposit -= abs($amount);
                } else {
                    $calculatedTotalDeposit += $amount;
                }
            }
            
            // Recalculate total_purchases from data_pencatatan
            $calculatedTotalPurchases = $this->dataPencatatan()
                ->get()
                ->sum(function ($item) {
                    return $this->calculateItemPrice($item);
                });
            
            // Update monthly_balances JSON field
            $monthlyBalances = $this->generateMonthlyBalances();
            
            // Use updateQuietly to prevent triggering Model Events
            $this->updateQuietly([
                'total_deposit' => $calculatedTotalDeposit,
                'total_purchases' => $calculatedTotalPurchases,
                'monthly_balances' => $monthlyBalances,
                'balance_last_updated_at' => now()
            ]);
            
            // Remove flag
            $this->setAttribute('_updating_balance', false);
            
            DB::commit();
            
            \Log::info('Balance refreshed successfully', [
                'user_id' => $this->id,
                'total_deposit' => $calculatedTotalDeposit,
                'total_purchases' => $calculatedTotalPurchases
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error refreshing balance', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * GENERATE MONTHLY BALANCES (Pure calculation)
     */
    private function generateMonthlyBalances()
    {
        $balances = [];
        $runningBalance = 0;
        
        // Get all months with activity
        $months = $this->getMonthsWithActivity();
        
        foreach ($months as $yearMonth) {
            $monthDeposits = $this->getDepositsForMonth($yearMonth);
            $monthPurchases = $this->getPurchasesForMonth($yearMonth);
            
            $runningBalance = $runningBalance + $monthDeposits - $monthPurchases;
            $balances[$yearMonth] = round($runningBalance, 2);
        }
        
        return $balances;
    }

    /**
     * CALCULATE ITEM PRICE (with period-specific pricing)
     */
    private function calculateItemPrice($item)
    {
        if ($item->harga_final > 0) {
            return floatval($item->harga_final);
        }
        
        $dataInput = $this->ensureArray($item->data_input);
        
        // For regular customers
        if (!$this->isFOB() && !empty($dataInput['pembacaan_awal']['waktu'])) {
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $yearMonth = $waktuAwal->format('Y-m');
            
            $pricingInfo = $this->getPricingForYearMonth($yearMonth, $waktuAwal);
            $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $this->koreksi_meter);
            $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik);
            
            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
            return $volumeSm3 * $hargaPerM3;
        }
        
        // For FOB customers
        if ($this->isFOB() && !empty($dataInput['waktu'])) {
            $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);
            $waktu = Carbon::parse($dataInput['waktu']);
            $yearMonth = $waktu->format('Y-m');
            
            $pricingInfo = $this->getPricingForYearMonth($yearMonth, $waktu);
            $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $this->harga_per_meter_kubik);
            
            return $volumeSm3 * $hargaPerM3;
        }
        
        return 0;
    }

    /**
     * GET MONTHS WITH ACTIVITY
     */
    private function getMonthsWithActivity()
    {
        $months = [];
        
        // Get months from deposit history
        $depositHistory = $this->ensureArray($this->deposit_history);
        foreach ($depositHistory as $deposit) {
            if (!empty($deposit['date'])) {
                $months[] = Carbon::parse($deposit['date'])->format('Y-m');
            }
        }
        
        // Get months from data pencatatan
        $dataPencatatan = $this->dataPencatatan()->get();
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $months[] = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            } elseif (!empty($dataInput['waktu'])) {
                $months[] = Carbon::parse($dataInput['waktu'])->format('Y-m');
            } elseif ($item->created_at) {
                $months[] = $item->created_at->format('Y-m');
            }
        }
        
        // Remove duplicates and sort
        $months = array_unique($months);
        sort($months);
        
        return $months;
    }

    /**
     * GET DEPOSITS FOR SPECIFIC MONTH
     */
    private function getDepositsForMonth($yearMonth)
    {
        $depositHistory = $this->ensureArray($this->deposit_history);
        $totalDeposits = 0;
        
        foreach ($depositHistory as $deposit) {
            if (!empty($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') === $yearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';
                    
                    if ($keterangan === 'pengurangan') {
                        $totalDeposits -= abs($amount);
                    } else {
                        $totalDeposits += $amount;
                    }
                }
            }
        }
        
        return $totalDeposits;
    }

    /**
     * GET PURCHASES FOR SPECIFIC MONTH
     */
    private function getPurchasesForMonth($yearMonth)
    {
        $dataPencatatan = $this->dataPencatatan()->get();
        $totalPurchases = 0;
        
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $itemDate = null;
            
            // Determine item date
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $itemDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            } elseif (!empty($dataInput['waktu'])) {
                $itemDate = Carbon::parse($dataInput['waktu']);
            } elseif ($item->created_at) {
                $itemDate = $item->created_at;
            }
            
            if ($itemDate && $itemDate->format('Y-m') === $yearMonth) {
                $totalPurchases += $this->calculateItemPrice($item);
            }
        }
        
        return $totalPurchases;
    }

    /**
     * CALCULATE TOTAL DEPOSITS UNTIL SPECIFIC PERIOD
     */
    private function calculateTotalDepositsUntil($yearMonth)
    {
        $depositHistory = $this->ensureArray($this->deposit_history);
        $totalDeposits = 0;
        
        foreach ($depositHistory as $deposit) {
            if (!empty($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->format('Y-m') <= $yearMonth) {
                    $amount = floatval($deposit['amount'] ?? 0);
                    $keterangan = $deposit['keterangan'] ?? 'penambahan';
                    
                    if ($keterangan === 'pengurangan') {
                        $totalDeposits -= abs($amount);
                    } else {
                        $totalDeposits += $amount;
                    }
                }
            }
        }
        
        return $totalDeposits;
    }

    /**
     * CALCULATE TOTAL PURCHASES UNTIL SPECIFIC PERIOD
     */
    private function calculateTotalPurchasesUntil($yearMonth)
    {
        $dataPencatatan = $this->dataPencatatan()->get();
        $totalPurchases = 0;
        
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $itemDate = null;
            
            // Determine item date
            if (!empty($dataInput['pembacaan_awal']['waktu'])) {
                $itemDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            } elseif (!empty($dataInput['waktu'])) {
                $itemDate = Carbon::parse($dataInput['waktu']);
            } elseif ($item->created_at) {
                $itemDate = $item->created_at;
            }
            
            if ($itemDate && $itemDate->format('Y-m') <= $yearMonth) {
                $totalPurchases += $this->calculateItemPrice($item);
            }
        }
        
        return $totalPurchases;
    }

    /**
     * FORCE UPDATE MONTHLY BALANCES (Re-enabled for pure MVC)
     */
    public function updateMonthlyBalances($startMonth = null)
    {
        return $this->refreshTotalBalances();
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
     * Check if user is customer or FOB (or MMBTU)
     * Fungsi untuk bisa menampilkan data di dashboard
     */
    public function isCustomerOrFOB()
    {
        return in_array($this->role, [self::ROLE_CUSTOMER, self::ROLE_FOB, self::ROLE_MMBTU]);
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

    /**
     * Check if user is keuangan
     */
    public function isKeuangan()
    {
        return $this->role === self::ROLE_KEUANGAN;
    }

    /**
     * Check if user is staff
     */
    public function isStaff()
    {
        return $this->role === self::ROLE_STAFF;
    }

    /**
     * Check if user is MMBTU customer
     */
    public function isMmbtu()
    {
        return $this->role === self::ROLE_MMBTU;
    }

    /**
     * Check if user is any type of customer (customer, fob, or mmbtu)
     */
    public function isAnyCustomer()
    {
        return in_array($this->role, [self::ROLE_CUSTOMER, self::ROLE_FOB, self::ROLE_MMBTU]);
    }

    /**
     * Add pricing history khusus MMBTU customer
     * Menyimpan harga_per_mmbtu_usd, pembagi_sm3_ke_mmbtu, tekanan, suhu, koreksi_meter
     */
    public function addPricingHistoryMmbtu($hargaPerMmbtuUsd, $pembajangSm3Mmbtu, $tekananKeluar, $suhu, $koreksiMeter, $customDate = null)
    {
        try {
            DB::beginTransaction();

            $hargaPerMmbtuUsd = floatval(str_replace(',', '.', $hargaPerMmbtuUsd));
            $pembajangSm3Mmbtu = floatval(str_replace(',', '.', $pembajangSm3Mmbtu));
            $tekananKeluar = floatval(str_replace(',', '.', $tekananKeluar));
            $suhu = floatval(str_replace(',', '.', $suhu));
            $koreksiMeter = floatval(str_replace(',', '.', $koreksiMeter));

            $pricingDate = $customDate ?: now();
            $yearMonth = $pricingDate->format('Y-m');

            $pricingEntry = [
                'date' => $pricingDate->format('Y-m-d H:i:s'),
                'year_month' => $yearMonth,
                'harga_per_meter_kubik' => 0, // tidak dipakai untuk MMBTU
                'harga_per_mmbtu_usd' => round($hargaPerMmbtuUsd, 4),
                'pembagi_sm3_ke_mmbtu' => round($pembajangSm3Mmbtu, 6),
                'tekanan_keluar' => round($tekananKeluar, 3),
                'suhu' => round($suhu, 2),
                'koreksi_meter' => round($koreksiMeter, 8),
                'is_mmbtu' => true,
            ];

            $pricingHistory = $this->ensureArray($this->pricing_history);

            $existingIndex = null;
            foreach ($pricingHistory as $index => $entry) {
                if (isset($entry['year_month']) && $entry['year_month'] === $yearMonth) {
                    $existingIndex = $index;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $pricingHistory[$existingIndex] = $pricingEntry;
            } else {
                $pricingHistory[] = $pricingEntry;
            }

            $this->setAttribute('pricing_history', $pricingHistory);

            $currentMonth = now()->format('Y-m');
            if ($yearMonth === $currentMonth) {
                $this->setAttribute('harga_per_mmbtu_usd', $hargaPerMmbtuUsd);
                $this->setAttribute('pembagi_sm3_ke_mmbtu', $pembajangSm3Mmbtu);
                $this->setAttribute('tekanan_keluar', $tekananKeluar);
                $this->setAttribute('suhu', $suhu);
                $this->setAttribute('koreksi_meter', $koreksiMeter);
            }

            $result = $this->save();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addPricingHistoryMmbtu', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Tambah deposit untuk MMBTU customer
     * Deposit disimpan dalam USD (mmbtu_amount * harga_satuan_usd)
     */
    public function addDepositMmbtu($mmbtuAmount, $hargaSatuanUsd, $description = null, $customDate = null, $keterangan = 'penambahan')
    {
        try {
            DB::beginTransaction();

            $mmbtuAmount = floatval($mmbtuAmount);
            $hargaSatuanUsd = floatval($hargaSatuanUsd);
            $totalUsd = $mmbtuAmount * $hargaSatuanUsd;

            $depositDate = $customDate ? $customDate : now();

            $depositEntry = [
                'date' => $depositDate->format('Y-m-d H:i:s'),
                'mmbtu_amount' => round($mmbtuAmount, 4),
                'harga_satuan_usd' => round($hargaSatuanUsd, 4),
                'amount' => round($totalUsd, 4), // total USD
                'keterangan' => $keterangan,
                'deskripsi' => $description,
                'is_mmbtu' => true,
            ];

            $depositHistory = $this->ensureArray($this->deposit_history);
            $depositHistory[] = $depositEntry;

            $this->total_deposit += $totalUsd;
            $this->deposit_history = $depositHistory;
            $this->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addDepositMmbtu', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Dapatkan harga per MMBTU (USD) untuk tanggal tertentu
     */
    public function getHargaPerMmbtuUsdForDate($date)
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $yearMonth = $carbonDate->format('Y-m');
        $pricingData = $this->getPricingForYearMonth($yearMonth, $carbonDate);
        return floatval($pricingData['harga_per_mmbtu_usd'] ?? 0);
    }

    /**
     * Dapatkan pembagi SM3 ke MMBTU untuk tanggal tertentu
     */
    public function getPembajangSm3MmbtuForDate($date)
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $yearMonth = $carbonDate->format('Y-m');
        $pricingData = $this->getPricingForYearMonth($yearMonth, $carbonDate);
        return floatval($pricingData['pembagi_sm3_ke_mmbtu'] ?? 1);
    }

    /**
     * Get closing balance untuk periode tertentu dari monthly_balances
     */
    public function getClosingBalanceForPeriod($yearMonth)
    {
        $monthlyBalances = $this->ensureArray($this->monthly_balances);
        return floatval($monthlyBalances[$yearMonth] ?? 0);
    }

    /**
     * Get all monthly balances sebagai array yang diformat
     */
    public function getFormattedMonthlyBalances()
    {
        $monthlyBalances = $this->ensureArray($this->monthly_balances);
        $formatted = [];
        
        foreach ($monthlyBalances as $yearMonth => $closingBalance) {
            $formatted[] = [
                'year_month' => $yearMonth,
                'period_label' => Carbon::createFromFormat('Y-m', $yearMonth)->format('F Y'),
                'closing_balance' => floatval($closingBalance),
                'closing_balance_formatted' => 'Rp ' . number_format($closingBalance, 2, ',', '.')
            ];
        }
        
        return $formatted;
    }

    /**
     * Get latest closing balance
     */
    public function getLatestClosingBalance()
    {
        $monthlyBalances = $this->ensureArray($this->monthly_balances);
        
        if (empty($monthlyBalances)) {
            return 0;
        }
        
        // Ambil periode terbaru
        $latestPeriod = max(array_keys($monthlyBalances));
        return floatval($monthlyBalances[$latestPeriod] ?? 0);
    }
}
