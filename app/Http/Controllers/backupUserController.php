<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserController extends Controller
{
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
                $request->input('tekanan_keluar'),
                $request->input('suhu')
            );

            $pricingDate = Carbon::parse($request->input('pricing_date'));
            $customer->addPricingHistory(
                $request->input('harga_per_meter_kubik'),
                $request->input('tekanan_keluar'),
                $request->input('suhu'),
                $koreksiMeter,
                $pricingDate
            );

            // Re-kalkulasi total pembelian berdasarkan pricing history baru
            $this->rekalkulasiTotalPembelian($customer);

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
    public function rekalkulasiTotalPembelian($customer)
    {
        // Reset total pembelian
        $customer->total_purchases = 0;

        // Ambil semua data pencatatan
        $dataPencatatans = $customer->dataPencatatan()->get();

        $totalPembelian = 0;

        foreach ($dataPencatatans as $dataPencatatan) {
            $dataInput = is_string($dataPencatatan->data_input)
                ? json_decode($dataPencatatan->data_input, true)
                : (is_array($dataPencatatan->data_input) ? $dataPencatatan->data_input : []);

            $volumeFlowMeter = $dataInput['volume_flow_meter'] ?? 0;

            // Ambil waktu pembacaan awal
            $waktuAwal = !empty($dataInput['pembacaan_awal']['waktu'])
                ? Carbon::parse($dataInput['pembacaan_awal']['waktu'])
                : null;

            if ($waktuAwal) {
                $yearMonth = $waktuAwal->format('Y-m');

                // Ambil pricing info untuk bulan tersebut
                $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

                // Hitung dengan pricing yang sesuai
                $koreksiMeter = $pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter;
                $hargaPerM3 = $pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik;

                $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
                $pembelian = $volumeSm3 * $hargaPerM3;

                $totalPembelian += $pembelian;
            }
        }

        // Update total pembelian
        $customer->total_purchases = $totalPembelian;
        $customer->save();

        return $totalPembelian;
    }
    public function getPricingHistory(Request $request, $customerId)
    {
        $customer = User::findOrFail($customerId);
        return response()->json([
            'pricing_history' => $customer->pricing_history ?? []
        ]);
    }

    /**
     * Hitung koreksi meter
     * Metode statis yang bisa digunakan di berbagai tempat
     */
    public static function hitungKoreksiMeter($tekananKeluar, $suhu)
    {
        // Perhitungan koreksi meter sesuai standar
        $A = ($tekananKeluar + 1.01325) / 1.01325;
        $B = 300 / ($suhu + 273);
        $C = 1 + (0.002 * $tekananKeluar);

        return $A * $B * $C;
    }
}
