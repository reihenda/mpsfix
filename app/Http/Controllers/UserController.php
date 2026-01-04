<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Query untuk Admin (admin, superadmin, keuangan)
        $adminQuery = User::query()
            ->whereIn('role', ['admin', 'superadmin', 'keuangan'])
            ->orderBy('role');

        // Query untuk Customer/FOB (customer, fob, demo)
        $customerQuery = User::query()
            ->whereIn('role', ['customer', 'fob', 'demo'])
            ->orderBy('role');

        // Filter berdasarkan pencarian untuk admin jika ada
        if ($request->has('search_admin') && !empty($request->search_admin)) {
            $searchTerm = $request->search_admin;
            $adminQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('role', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter berdasarkan pencarian untuk customer jika ada
        if ($request->has('search_customer') && !empty($request->search_customer)) {
            $searchTerm = $request->search_customer;
            $customerQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('role', 'like', '%' . $searchTerm . '%')
                    ->orWhere('no_kontrak', 'like', '%' . $searchTerm . '%')
                    ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nomor_tlpn', 'like', '%' . $searchTerm . '%');
            });
        }

        $adminUsers = $adminQuery->get();
        $customerUsers = $customerQuery->get();

        // Deteksi jika request adalah AJAX untuk pencarian real-time
        if ($request->ajax()) {
            if ($request->has('search_admin')) {
                return response()->json([
                    'html' => view('user.partials.admin-table', ['users' => $adminUsers])->render(),
                ]);
            } elseif ($request->has('search_customer')) {
                return response()->json([
                    'html' => view('user.partials.customer-table', ['users' => $customerUsers])->render(),
                ]);
            }
        }

        return view('user.index', compact('adminUsers', 'customerUsers'));
    }

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

    /**
     * Update customer pricing and meter correction
     */
    public function addDeposit(Request $request, $userId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);

        // Validate user permissions (only admin can add deposit)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menambah deposit');
        }
        $depositDate = Carbon::parse($request->deposit_date);

        // Add deposit
        if ($user->addDeposit($request->amount, $request->description, $depositDate)) {
            return redirect()->back()->with('success', 'Deposit berhasil ditambahkan');
        } else {
            return redirect()->back()->with('error', 'Gagal menambahkan deposit');
        }
    }

    /**
     * Reduce balance (pengurangan saldo)
     */
    public function reduceBalance(Request $request, $userId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reduction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);

        // Validate user permissions (only admin can reduce balance)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengurangi saldo');
        }
        
        $reductionDate = Carbon::parse($request->reduction_date);

        // Reduce balance
        if ($user->reduceBalance($request->amount, $request->description, $reductionDate)) {
            return redirect()->back()->with('success', 'Saldo berhasil dikurangi');
        } else {
            return redirect()->back()->with('error', 'Gagal mengurangi saldo');
        }
    }

    /**
     * Zero balance (nol-kan saldo)
     */
    public function zeroBalance(Request $request, $userId)
    {
        $request->validate([
            'zero_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);

        // Validate user permissions (only admin can zero balance)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menol-kan saldo');
        }
        
        $zeroDate = Carbon::parse($request->zero_date);

        // Zero balance
        if ($user->zeroBalance($request->description, $zeroDate)) {
            return redirect()->back()->with('success', 'Saldo berhasil dinolkan');
        } else {
            return redirect()->back()->with('error', 'Gagal menolkan saldo');
        }
    }

    public function removeDeposit(Request $request, $userId)
    {
        // Validate user permissions (only admin can remove deposit)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk menghapus deposit');
        }

        $request->validate([
            'deposit_index' => 'required|integer|min:0'
        ]);

        $user = User::findOrFail($userId);

        if ($user->removeDeposit($request->deposit_index)) {
            return redirect()->back()->with('success', 'Deposit berhasil dihapus');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus deposit');
        }
    }

    /**
     * Update deposit entry
     */
    public function updateDeposit(Request $request, $userId)
    {
        // Validate user permissions (only admin can edit deposit)
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengedit deposit');
        }

        // Validate input
        $request->validate([
            'deposit_index' => 'required|integer|min:0',
            'amount' => 'required|numeric',
            'deposit_date' => 'required|date',
            'keterangan' => 'required|in:penambahan,pengurangan',
            'description' => 'nullable|string|max:255'
        ]);

        $user = User::findOrFail($userId);
        
        // Parse tanggal
        $depositDate = Carbon::parse($request->deposit_date);

        // Handle amount berdasarkan keterangan
        $amount = floatval($request->amount);
        if ($request->keterangan === 'pengurangan') {
            $amount = -abs($amount); // Pastikan negatif untuk pengurangan
        } else {
            $amount = abs($amount); // Pastikan positif untuk penambahan
        }

        // Edit deposit
        if ($user->editDeposit(
            $request->deposit_index,
            $amount,
            $request->description,
            $depositDate,
            $request->keterangan
        )) {
            // Rekalkulasi total deposit dan monthly balances
            $this->rekalkulasiTotalDeposit($user);
            $user->updateMonthlyBalances();
            
            return redirect()->back()->with('success', 'Deposit berhasil diperbarui');
        } else {
            return redirect()->back()->with('error', 'Gagal memperbarui deposit');
        }
    }

    public function updateCustomerPricing(Request $request, $customerId)
    {
        // Pastikan hanya admin atau super admin yang bisa mengakses
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin');
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'harga_per_meter_kubik' => 'required|numeric|min:0',
            'tekanan_keluar' => 'required|numeric|min:0',
            'suhu' => 'required|numeric',
            'pricing_date' => 'required|date', // Tambahkan validasi untuk tanggal pricing
        ], [
            'harga_per_meter_kubik.required' => 'Harga per m³ harus diisi',
            'harga_per_meter_kubik.numeric' => 'Harga per m³ harus berupa angka',
            'harga_per_meter_kubik.min' => 'Harga per m³ tidak boleh kurang dari 0',
            'tekanan_keluar.required' => 'Tekanan keluar harus diisi',
            'tekanan_keluar.numeric' => 'Tekanan keluar harus berupa angka',
            'tekanan_keluar.min' => 'Tekanan keluar tidak boleh kurang dari 0',
            'suhu.required' => 'Suhu harus diisi',
            'suhu.numeric' => 'Suhu harus berupa angka',
            'pricing_date.required' => 'Tanggal pricing harus diisi',
            'pricing_date.date' => 'Format tanggal pricing tidak valid',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Cari customer
            $customer = User::findOrFail($customerId);

            // Hitung koreksi meter dan tambahkan ke pricing history
            $koreksiMeter = self::hitungKoreksiMeter(
                floatval($request->input('tekanan_keluar')),
                floatval($request->input('suhu'))
            );

            $pricingDate = Carbon::parse($request->input('pricing_date'));
            $customer->addPricingHistory(
                floatval($request->input('harga_per_meter_kubik')),
                floatval($request->input('tekanan_keluar')),
                floatval($request->input('suhu')),
                $koreksiMeter,
                $pricingDate
            );

            // Rekalkulasi total pembelian untuk semua data
            $this->rekalkulasiTotalPembelian($customer);

            // Update saldo bulanan mulai dari bulan periode pricing
            $yearMonth = $pricingDate->format('Y-m');
            $customer->updateMonthlyBalances($yearMonth);

            // Rekalkulasi harga untuk setiap data pencatatan dalam periode pricing
            $dataPencatatans = $customer->dataPencatatan()->get();
            foreach ($dataPencatatans as $dataPencatatan) {
                $dataInput = $this->ensureArray($dataPencatatan->data_input);
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    continue;
                }

                $waktuPencatatan = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                $waktuPencatatanYM = $waktuPencatatan->format('Y-m');

                // Jika data berada dalam bulan yang sama dengan pricing, hitung ulang harga
                if ($waktuPencatatanYM === $yearMonth) {
                    $dataPencatatan->hitungHarga();
                }
            }

            DB::commit();

            // Redirect dengan refresh data
            return redirect()->route('data-pencatatan.customer-detail', [
                'customer' => $customer->id,
                'refresh' => true
            ])->with('success', 'Harga dan koreksi meter untuk periode ' . $pricingDate->format('F Y') . ' berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Update pricing untuk periode khusus (rentang tanggal)
     */
    public function updateCustomerPricingKhusus(Request $request, $customerId)
    {
        // Pastikan hanya admin atau super admin yang bisa mengakses
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin');
        }

        // Validasi input untuk periode khusus
        $validator = Validator::make($request->all(), [
            'harga_per_meter_kubik' => 'required|numeric|min:0',
            'tekanan_keluar' => 'required|numeric|min:0',
            'suhu' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'harga_per_meter_kubik.required' => 'Harga per m³ harus diisi',
            'harga_per_meter_kubik.numeric' => 'Harga per m³ harus berupa angka',
            'harga_per_meter_kubik.min' => 'Harga per m³ tidak boleh kurang dari 0',
            'tekanan_keluar.required' => 'Tekanan keluar harus diisi',
            'tekanan_keluar.numeric' => 'Tekanan keluar harus berupa angka',
            'tekanan_keluar.min' => 'Tekanan keluar tidak boleh kurang dari 0',
            'suhu.required' => 'Suhu harus diisi',
            'suhu.numeric' => 'Suhu harus berupa angka',
            'start_date.required' => 'Tanggal awal harus diisi',
            'start_date.date' => 'Format tanggal awal tidak valid',
            'end_date.required' => 'Tanggal akhir harus diisi',
            'end_date.date' => 'Format tanggal akhir tidak valid',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal awal',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Cari customer
            $customer = User::findOrFail($customerId);

            // Hitung koreksi meter
            $koreksiMeter = self::hitungKoreksiMeter(
                floatval($request->input('tekanan_keluar')),
                floatval($request->input('suhu'))
            );

            // Parse tanggal awal dan akhir
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            // Tambahkan periode khusus ke pricing history
            $customer->addCustomPeriodPricing(
                floatval($request->input('harga_per_meter_kubik')),
                floatval($request->input('tekanan_keluar')),
                floatval($request->input('suhu')),
                $koreksiMeter,
                $startDate,
                $endDate
            );

            // Rekalkulasi total pembelian untuk semua data
            $this->rekalkulasiTotalPembelian($customer);

            // Update saldo bulanan mulai dari bulan awal periode khusus
            $startMonthYear = $startDate->format('Y-m');
            
            // PERBAIKAN: Panggil updateMonthlyBalances dengan logging tambahan
            \Log::info('Menjalankan updateMonthlyBalances setelah tambah periode khusus', [
                'user_id' => $customer->id,
                'startMonth' => $startMonthYear,
                'periode_khusus' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'harga_per_m3' => $request->input('harga_per_meter_kubik'),
                    'koreksi_meter' => $koreksiMeter
                ]
            ]);
            
            $updateResult = $customer->updateMonthlyBalances($startMonthYear);
            
            \Log::info('Hasil updateMonthlyBalances setelah tambah periode khusus', [
                'user_id' => $customer->id,
                'result' => $updateResult
            ]);

            // Rekalkulasi harga untuk setiap data pencatatan dalam periode khusus
            $dataPencatatans = $customer->dataPencatatan()->get();
            
            foreach ($dataPencatatans as $dataPencatatan) {
                $dataInput = $this->ensureArray($dataPencatatan->data_input);
                if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                    continue;
                }

                $waktuPencatatan = Carbon::parse($dataInput['pembacaan_awal']['waktu']);

                // Jika data berada dalam periode khusus, hitung ulang harga
                if ($waktuPencatatan->between($startDate, $endDate)) {
                    $dataPencatatan->hitungHarga();
                }
            }
            
            // Panggil kembali rekalkulasiTotalPembelian untuk memastikan total
            // pembelian terperbarui dengan benar setelah seluruh perhitungan dilakukan
            $finalPurchaseResult = $this->rekalkulasiTotalPembelian($customer);
            
            // Update saldo bulanan lagi untuk memastikan konsistensi data
            $customer->updateMonthlyBalances($startMonthYear);

            DB::commit();

            // Format rentang tanggal untuk pesan sukses
            $dateRangeString = $startDate->format('d M Y') . ' sampai ' . $endDate->format('d M Y');

            // Redirect dengan refresh data
            return redirect()->route('data-pencatatan.customer-detail', [
                'customer' => $customer->id,
                'refresh' => true
            ])->with('success', 'Harga dan koreksi meter untuk periode khusus ' . $dateRangeString . ' berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan periode khusus: ' . $e->getMessage());
        }
    }

    /**
     * Rekalkulasi total deposit berdasarkan deposit_history dengan keterangan
     * Menangani deposit dengan keterangan 'penambahan' dan 'pengurangan'
     * 
     * @param User $customer
     * @return float Total deposit yang sudah diperbaiki
     */
    public function rekalkulasiTotalDeposit($customer)
    {
        try {
            DB::beginTransaction();
            
            \Log::info('Memulai rekalkulasiTotalDeposit', [
                'user_id' => $customer->id,
                'name' => $customer->name,
                'current_total_deposit' => $customer->total_deposit
            ]);

            // Reset total deposit
            $customer->total_deposit = 0;

            // Ambil semua deposit history
            $depositHistory = $this->ensureArray($customer->deposit_history);

            $totalDeposit = 0;
            $totalPenambahan = 0;
            $totalPengurangan = 0;
            
            // Logging untuk debugging
            \Log::info('Jumlah deposit history untuk rekalkulasi', [
                'user_id' => $customer->id,
                'total_entries' => count($depositHistory)
            ]);

            foreach ($depositHistory as $index => $deposit) {
                $amount = floatval($deposit['amount'] ?? 0);
                $keterangan = $deposit['keterangan'] ?? 'penambahan';
                $tanggal = $deposit['date'] ?? 'N/A';

                // PERBAIKAN: Handle deposit berdasarkan keterangan
                if ($keterangan === 'pengurangan') {
                    // Jika keterangan pengurangan, kurangi dari total
                    $totalDeposit -= abs($amount);
                    $totalPengurangan += abs($amount);
                    
                    \Log::debug('Mengurangi deposit', [
                        'index' => $index,
                        'amount' => $amount,
                        'abs_amount' => abs($amount),
                        'tanggal' => $tanggal,
                        'running_total' => $totalDeposit
                    ]);
                } else {
                    // Jika keterangan penambahan (atau tidak ada keterangan), tambahkan
                    $totalDeposit += $amount;
                    $totalPenambahan += $amount;
                    
                    \Log::debug('Menambahkan deposit', [
                        'index' => $index,
                        'amount' => $amount,
                        'tanggal' => $tanggal,
                        'running_total' => $totalDeposit
                    ]);
                }
            }

            // Update total deposit - pastikan numerik
            $customer->total_deposit = floatval($totalDeposit);
            $result = $customer->save();

            \Log::info('Hasil rekalkulasi total deposit', [
                'user_id' => $customer->id,
                'total_penambahan' => $totalPenambahan,
                'total_pengurangan' => $totalPengurangan,
                'total_deposit_final' => $totalDeposit,
                'save_result' => $result
            ]);

            DB::commit();

            return $totalDeposit;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in rekalkulasiTotalDeposit: ' . $e->getMessage(), [
                'user_id' => $customer->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Rekalkulasi total pembelian untuk customer
     * 
     * @param User $customer
     * @return float Total pembelian yang sudah diperbaiki
     */
    public function rekalkulasiTotalPembelian($customer)
    {
        try {
            DB::beginTransaction();

            // Log awal proses rekalkulasi
            \Log::info('Memulai rekalkulasiTotalPembelian', [
                'user_id' => $customer->id,
                'name' => $customer->name
            ]);

            // Reset total pembelian
            $customer->total_purchases = 0;

            // Ambil semua data pencatatan
            $dataPencatatans = $customer->dataPencatatan()->get();

            $totalPembelian = 0;
            
            // Logging untuk debugging
            \Log::info('Jumlah data pencatatan untuk rekalkulasi', [
                'user_id' => $customer->id,
                'total_data' => count($dataPencatatans)
            ]);

            foreach ($dataPencatatans as $dataPencatatan) {
                $dataInput = $this->ensureArray($dataPencatatan->data_input);

                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

                // Ambil waktu pembacaan awal
                $waktuAwal = !empty($dataInput['pembacaan_awal']['waktu'])
                    ? Carbon::parse($dataInput['pembacaan_awal']['waktu'])
                    : null;

                if ($waktuAwal) {
                    $yearMonth = $waktuAwal->format('Y-m');

                    // PERBAIKAN: Gunakan tanggal spesifik untuk mendapatkan pricing yang tepat
                    $pricingInfo = $customer->getPricingForYearMonth($yearMonth, $waktuAwal);

                    // Hitung dengan pricing yang sesuai
                    $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                    $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);

                    $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                    $pembelian = $volumeSm3 * $hargaPerM3;
                    
                    // Update juga harga final di data pencatatan
                    $dataPencatatan->harga_final = round($pembelian, 2);
                    $dataPencatatan->save();

                    // Logging untuk debugging
                    \Log::debug('Perhitungan item pembelian dalam rekalkulasi', [
                        'record_id' => $dataPencatatan->id,
                        'date' => $waktuAwal->format('Y-m-d H:i:s'),
                        'volumeFlowMeter' => $volumeFlowMeter,
                        'koreksiMeter' => $koreksiMeter,
                        'hargaPerM3' => $hargaPerM3,
                        'volumeSm3' => $volumeSm3,
                        'pembelian' => $pembelian,
                        'harga_final_updated' => $dataPencatatan->harga_final,
                        'is_periode_khusus' => isset($pricingInfo['type']) && $pricingInfo['type'] === 'custom_period'
                    ]);

                    $totalPembelian += $pembelian;
                }
            }

            // Update total pembelian - pastikan numerik
            $customer->total_purchases = floatval($totalPembelian);
            $result = $customer->save();

            \Log::info('Hasil rekalkulasi total pembelian', [
                'user_id' => $customer->id,
                'total_pembelian' => $totalPembelian,
                'save_result' => $result
            ]);

            // Update saldo bulanan setelah perhitungan ulang total pembelian
            $customer->updateMonthlyBalances();

            DB::commit();

            return $totalPembelian;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in rekalkulasiTotalPembelian: ' . $e->getMessage(), [
                'user_id' => $customer->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Update FOB pricing for a specific period
     *
     * @param Request $request
     * @param int $fobId
     * @return \Illuminate\Http\Response
     */
    public function updateFobPricing(Request $request, $customerId)
    {
        try {
            // Debug request untuk troubleshooting
            \Log::info('FOB Pricing Update Request', [
                'request_data' => $request->all(),
                'customer_id' => $customerId,
                'is_ajax' => $request->ajax() || $request->wantsJson()
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'harga_per_meter_kubik' => 'required|numeric|min:0',
                'pricing_date' => 'required|date_format:Y-m', // Format Y-m untuk input month
            ]);

            // Jika validasi gagal
            if ($validator->fails()) {
                \Log::error('FOB Pricing Validation Failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();
            // Cari customer
            $customer = User::findOrFail($customerId);

            // Parse pricing date - support format Y-m dari input month
            $pricingDateStr = $request->input('pricing_date');
            $pricingDate = Carbon::createFromFormat('Y-m', $pricingDateStr)->firstOfMonth();
            
            \Log::info('Adding FOB Pricing', [
                'date_str' => $pricingDateStr,
                'parsed_date' => $pricingDate->format('Y-m-d H:i:s'),
                'price' => $request->input('harga_per_meter_kubik')
            ]);
            
            // Panggil method di model untuk menyimpan pricing
            $customer->addPricingHistoryfob(
                floatval($request->input('harga_per_meter_kubik')),
                $pricingDate
            );

            // Call the rekalkulasi method to update total purchases
            $this->rekalkulasiTotalPembelianFob($customer);

            DB::commit();
            
            \Log::info('FOB Pricing Updated Successfully', [
                'customer_id' => $customer->id
            ]);

            // Redirect dengan refresh data
            return redirect()->route('data-pencatatan.fob-detail', [
                'customer' => $customer->id,
                'refresh' => true
            ])->with('success', 'Harga untuk periode ' . $pricingDate->format('F Y') . ' berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in updateFobPricing', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Rekalkulasi total pembelian khusus FOB dengan algoritma presisi tinggi
     */
    public function rekalkulasiTotalPembelianFob($fob)
    {
        try {
            DB::beginTransaction();

            // Reset total pembelian
            $fob->total_purchases = 0;

            // Ambil semua data pencatatan dengan urutan berdasarkan tanggal
            $dataPencatatans = $fob->dataPencatatan()->orderBy('created_at')->get();

            $totalPembelian = 0;
            $inconsistentRecords = 0; // Counter untuk record yang tidak konsisten

            foreach ($dataPencatatans as $dataPencatatan) {
                $dataInput = $this->ensureArray($dataPencatatan->data_input);

                // Untuk FOB, kita menggunakan volume_sm3 langsung
                $volumeSm3 = floatval($dataInput['volume_sm3'] ?? 0);

                // Ambil waktu pencatatan dengan prioritas pada format 'waktu'
                $waktu = null;
                if (!empty($dataInput['waktu'])) {
                    $waktu = Carbon::parse($dataInput['waktu']);
                } elseif (!empty($dataInput['pembacaan_awal']['waktu'])) {
                    $waktu = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                } elseif ($dataPencatatan->created_at) {
                    $waktu = $dataPencatatan->created_at;
                }
                
                // Jika tidak ada waktu valid, gunakan harga_final jika tersedia
                if (!$waktu) {
                    if ($dataPencatatan->harga_final > 0) {
                        $totalPembelian += $dataPencatatan->harga_final;
                    }
                    continue;
                }

                $yearMonth = $waktu->format('Y-m');

                // SELALU hitung berdasarkan pricing terbaru (calculated) untuk akurasi
                if ($volumeSm3 > 0) {
                    // Ambil pricing info untuk bulan tersebut
                    $pricingInfo = $fob->getPricingForYearMonth($yearMonth, $waktu);

                    // Hitung dengan pricing yang sesuai (SELALU calculated, bukan harga_final lama)
                    $hargaPerM3 = floatval($pricingInfo['harga_per_meter_kubik'] ?? $fob->harga_per_meter_kubik);
                    $pembelian = $volumeSm3 * $hargaPerM3;
                    
                    // Update harga final di database untuk konsistensi
                    $oldHargaFinal = $dataPencatatan->harga_final;
                    $dataPencatatan->harga_final = round($pembelian, 2);
                    $dataPencatatan->save();
                    
                    // Track jika ada perbedaan dari harga_final lama
                    if (abs($oldHargaFinal - $pembelian) > 0.01) {
                        $inconsistentRecords++;
                    }
                    
                    $totalPembelian += $pembelian;
                }
            }

            // Update total pembelian - pastikan numerik dengan pembulatan yang konsisten
            $fob->total_purchases = round(floatval($totalPembelian), 2);
            $result = $fob->save();

            DB::commit();

            return $fob->total_purchases;
        } catch (\Exception $e) {
            DB::rollBack();
            return 0;
        }
    }

    public function getPricingHistory(Request $request, $customerId)
    {
        $customer = User::findOrFail($customerId);
        return response()->json([
            'pricing_history' => $this->ensureArray($customer->pricing_history)
        ]);
    }

    /**
     * Sinkronisasi saldo tanpa mengembalikan respons redirect (untuk sinkronisasi otomatis)
     *
     * @param User $customer Customer yang akan disinkronisasi saldonya
     * @return boolean Hasil sinkronisasi (true jika berhasil)
     */
    public function syncBalanceSilent($customer)
    {
        try {
            DB::beginTransaction();
            
            // Log untuk debugging
            \Log::info('Memulai sinkronisasi saldo silent', [
                'user_id' => $customer->id,
                'name' => $customer->name,
                'current_total_deposit' => $customer->total_deposit,
                'current_total_purchases' => $customer->total_purchases,
                'current_balance' => $customer->getCurrentBalance()
            ]);
            
            // 1. Rekalkulasi total pembelian untuk mendapatkan nilai yang akurat
            if ($customer->isFOB()) {
                $newTotalPurchases = $this->rekalkulasiTotalPembelianFob($customer);
                \Log::info('Hasil rekalkulasi total pembelian FOB', [
                    'user_id' => $customer->id,
                    'new_total_purchases' => $newTotalPurchases,
                    'old_total_purchases' => $customer->total_purchases,
                    'difference' => $newTotalPurchases - $customer->total_purchases
                ]);
            } else {
                $newTotalPurchases = $this->rekalkulasiTotalPembelian($customer);
                \Log::info('Hasil rekalkulasi total pembelian customer', [
                    'user_id' => $customer->id,
                    'new_total_purchases' => $newTotalPurchases
                ]);
            }
            
            // 2. Pastikan total deposit dan total purchases dihitung dengan benar
            $depositHistory = $this->ensureArray($customer->deposit_history);
            $calculatedTotalDeposit = 0;
            
            // Hitung ulang total deposit dari seluruh riwayat deposit
            foreach ($depositHistory as $deposit) {
                $calculatedTotalDeposit += floatval($deposit['amount'] ?? 0);
            }
            
            // Update total deposit jika berbeda untuk memastikan konsistensi data
            if (abs($calculatedTotalDeposit - $customer->total_deposit) > 0.01) {
                \Log::info('Memperbarui total deposit', [
                    'user_id' => $customer->id,
                    'old_total_deposit' => $customer->total_deposit,
                    'calculated_total_deposit' => $calculatedTotalDeposit,
                    'difference' => $calculatedTotalDeposit - $customer->total_deposit
                ]);
                
                $customer->total_deposit = $calculatedTotalDeposit;
                $customer->save();
            }
            
            // 3. Perbarui monthly_balances untuk semua periode dengan data terbaru
            // Mulai dari waktu yang jauh ke belakang untuk memastikan semua data tercakup
            $updateResult = $customer->updateMonthlyBalances();
            \Log::info('Hasil update monthly_balances dengan data lengkap', [
                'user_id' => $customer->id,
                'success' => $updateResult ? 'true' : 'false'
            ]);
            
            
            // 4. PERBAIKAN: Tambahkan validasi akhir dengan perbandingan terhadap total aktual
            try {
                // Reload customer dari database untuk memastikan data terkini
                $freshCustomer = User::find($customer->id);
                if ($freshCustomer) {
                    $currentMonth = now()->format('Y-m');
                    $monthlyBalances = $this->ensureArray($freshCustomer->monthly_balances);
                    $currentTotalBalance = $freshCustomer->getCurrentBalance();
                    
                    // Identifikasi bulan terakhir yang ada di monthly_balances
                    $lastMonthKey = null;
                    $lastMonthBalance = null;
                    foreach ($monthlyBalances as $month => $balance) {
                        if ($lastMonthKey === null || $month > $lastMonthKey) {
                            $lastMonthKey = $month;
                            $lastMonthBalance = $balance;
                        }
                    }
                    
                    \Log::info('Validasi akhir sinkronisasi saldo', [
                        'user_id' => $freshCustomer->id,
                        'role' => $freshCustomer->role,
                        'current_month' => $currentMonth,
                        'last_month_in_balances' => $lastMonthKey,
                        'last_month_balance' => $lastMonthBalance,
                        'total_balance' => $currentTotalBalance,
                        'total_deposit' => $freshCustomer->total_deposit,
                        'total_purchases' => $freshCustomer->total_purchases
                    ]);
                    
                    // Jika perbedaan signifikan dan ini adalah FOB, coba sekali lagi
                    if ($lastMonthBalance !== null && 
                        abs($lastMonthBalance - $currentTotalBalance) > 0.01 && 
                        $freshCustomer->isFOB()) {
                            
                        \Log::warning('Melakukan satu kali lagi update monthly_balances untuk FOB', [
                            'user_id' => $freshCustomer->id,
                            'role' => $freshCustomer->role,
                            'last_month' => $lastMonthKey,
                            'last_month_balance' => $lastMonthBalance, 
                            'total_balance' => $currentTotalBalance,
                            'difference' => $currentTotalBalance - $lastMonthBalance
                        ]);
                        
                        // Untuk kasus FOB, kita coba lagi dengan waktu start yang lebih jauh
                        $fourYearsAgo = Carbon::now()->subYears(4)->format('Y-m');
                        $freshCustomer->updateMonthlyBalances($fourYearsAgo);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error saat validasi akhir sinkronisasi saldo: ' . $e->getMessage(), [
                    'user_id' => $customer->id, 
                    'role' => $customer->role,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Lanjutkan proses meskipun ada error
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error dalam sinkronisasi saldo silent: ' . $e->getMessage(), [
                'user_id' => $customer->id,
                'role' => $customer->role,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Sinkronisasi saldo untuk menangani perbedaan antara total saldo dan saldo bulanan
     *
     * @param int $userId
     * @return \Illuminate\Http\Response
     */
    public function syncBalance($userId)
    {
        try {
            DB::beginTransaction();
            
            // Cari customer
            $customer = User::findOrFail($userId);
            
            // Pastikan hanya admin atau superadmin yang bisa melakukan sinkronisasi
            if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk melakukan sinkronisasi saldo');
            }
            
            // Gunakan metode syncBalanceSilent
            $result = $this->syncBalanceSilent($customer);
            
            if ($result) {
                return redirect()->back()->with('success', 'Sinkronisasi saldo berhasil dilakukan');
            } else {
                return redirect()->back()->with('error', 'Terjadi masalah saat menyinkronkan saldo');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error dalam syncBalance: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Gagal melakukan sinkronisasi saldo: ' . $e->getMessage());
        }
    }

    /**
     * Hitung koreksi meter
     * Metode statis yang bisa digunakan di berbagai tempat
     */
    public static function hitungKoreksiMeter($tekananKeluar, $suhu)
    {
        // Ensure numeric values
        $tekananKeluar = floatval($tekananKeluar);
        $suhu = floatval($suhu);

        // Perhitungan koreksi meter sesuai standar
        $A = ($tekananKeluar + 1.01325) / 1.01325;
        $B = 300 / ($suhu + 273);
        $C = 1 + (0.002 * $tekananKeluar);

        return $A * $B * $C;
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(User $user)
    {
        // Pastikan user yang login adalah admin atau superadmin
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['error' => 'Anda tidak memiliki izin'], 403);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Pastikan user yang login adalah admin atau superadmin
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin');
        }

        // Validasi data input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,keuangan,customer,fob,demo',
            'password' => 'nullable|string|min:3',
            'no_kontrak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'nomor_tlpn' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'role.required' => 'Role harus dipilih',
            'role.in' => 'Role tidak valid',
            'password.min' => 'Password minimal 3 karakter',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Update data user
            $user->name = $request->name;
            $user->email = $request->email;
            $user->role = $request->role;
            $user->no_kontrak = $request->no_kontrak;
            $user->alamat = $request->alamat;
            $user->nomor_tlpn = $request->nomor_tlpn;

            // Update password jika diisi
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            DB::commit();

            return redirect()->route('user.index')->with('success', 'User berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, Request $request)
    {
        // Pastikan user yang login adalah admin atau superadmin
        if (!Auth::user()->isAdmin() && !Auth::user()->isSuperAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Anda tidak memiliki izin'], 403);
            }
            return redirect()->route('user.index')->with('error', 'Anda tidak memiliki izin');
        }

        // Cek apakah user yang dihapus adalah superadmin
        if ($user->role === 'superadmin') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'User superadmin tidak dapat dihapus'], 403);
            }
            return redirect()->route('user.index')->with('error', 'User superadmin tidak dapat dihapus');
        }

        // Cek apakah user yang dihapus adalah user yang sedang login
        if ($user->id === Auth::id()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Anda tidak dapat menghapus akun yang sedang digunakan'], 403);
            }
            return redirect()->route('user.index')->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan');
        }

        try {
            DB::beginTransaction();

            // Hapus user
            $user->delete();

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => 'User berhasil dihapus']);
            }
            return redirect()->route('user.index')->with('success', 'User berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menghapus user: ' . $e->getMessage()], 500);
            }
            return redirect()->route('user.index')->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
