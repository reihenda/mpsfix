<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Concerns\CompressesTripPhotos;
use App\Http\Controllers\Controller;
use App\Models\AlamatPengambilan;
use App\Models\NomorPolisi;
use App\Models\OperatorTrip;
use App\Models\TripLokasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorTripController extends Controller
{
    use CompressesTripPhotos;

    public function index()
    {
        $user = Auth::user();
        $activeTrip = OperatorTrip::where('user_id', $user->id)
            ->where('status', 'berangkat')
            ->latest('waktu_berangkat')
            ->first();

        if ($activeTrip) {
            $waLink = $this->buildWhatsAppLink($activeTrip);
            return view('operator.trip.sampai', compact('activeTrip', 'waLink'));
        }

        $lokasiOptions = $this->getLokasiOptions();
        $nopolOptions = NomorPolisi::orderBy('nopol')->pluck('nopol');

        $tripSelesai = null;
        $waLinkSelesai = null;
        $tripSelesaiId = session('trip_selesai_id');
        if ($tripSelesaiId) {
            $tripSelesai = OperatorTrip::find($tripSelesaiId);
            if ($tripSelesai) {
                $waLinkSelesai = $this->buildWhatsAppLink($tripSelesai);
            }
        }

        return view('operator.trip.berangkat', compact('lokasiOptions', 'nopolOptions', 'tripSelesai', 'waLinkSelesai'));
    }

    public function storeBerangkat(Request $request)
    {
        $user = Auth::user();

        $sudahAdaTripAktif = OperatorTrip::where('user_id', $user->id)
            ->where('status', 'berangkat')
            ->exists();

        if ($sudahAdaTripAktif) {
            return redirect()->route('operator.trip.index')
                ->with('error', 'Selesaikan trip yang sedang berjalan sebelum absen berangkat baru.');
        }

        $validated = $request->validate([
            'foto' => 'required|image|max:10240',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'asal_value' => 'required|string',
            'asal_baru' => 'required_if:asal_value,new|nullable|string|max:255',
            'tujuan_value' => 'required|string',
            'tujuan_baru' => 'required_if:tujuan_value,new|nullable|string|max:255',
            'tekanan' => 'required|numeric|min:0',
            'nopol_value' => 'required|string',
            'nopol_manual' => 'required_if:nopol_value,manual|nullable|string|max:20',
        ]);

        $asal = $this->resolveLokasi($validated['asal_value'], $validated['asal_baru'] ?? null, $user);
        $tujuan = $this->resolveLokasi($validated['tujuan_value'], $validated['tujuan_baru'] ?? null, $user);
        $nopol = $validated['nopol_value'] === 'manual' ? $validated['nopol_manual'] : $validated['nopol_value'];

        $fotoPath = $this->compressAndStorePhoto($request->file('foto'), 'trip-photos/berangkat');

        OperatorTrip::create([
            'operator_gtm_id' => $user->operator_gtm_id,
            'user_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'nopol' => $nopol,
            'asal_type' => $asal['type'],
            'asal_id' => $asal['id'],
            'asal_nama' => $asal['nama'],
            'tujuan_type' => $tujuan['type'],
            'tujuan_id' => $tujuan['id'],
            'tujuan_nama' => $tujuan['nama'],
            'tekanan' => $validated['tekanan'],
            'foto_berangkat_path' => $fotoPath,
            'latitude_berangkat' => $validated['latitude'] ?? null,
            'longitude_berangkat' => $validated['longitude'] ?? null,
            'waktu_berangkat' => now(),
            'status' => 'berangkat',
        ]);

        return redirect()->route('operator.trip.index')
            ->with('success', 'Absen berangkat berhasil disimpan.');
    }

    public function storeSampai(Request $request, OperatorTrip $trip)
    {
        if ($trip->user_id !== Auth::id() || $trip->status !== 'berangkat') {
            abort(403);
        }

        $validated = $request->validate([
            'foto' => 'required|image|max:10240',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $fotoPath = $this->compressAndStorePhoto($request->file('foto'), 'trip-photos/sampai');

        $trip->update([
            'foto_sampai_path' => $fotoPath,
            'latitude_sampai' => $validated['latitude'] ?? null,
            'longitude_sampai' => $validated['longitude'] ?? null,
            'waktu_sampai' => now(),
            'status' => 'selesai',
        ]);

        session()->flash('trip_selesai_id', $trip->id);

        return redirect()->route('operator.trip.index')
            ->with('success', 'Absen sampai berhasil disimpan.');
    }

    /**
     * Bangun link wa.me berisi teks laporan trip siap kirim (format tetap sama
     * baik saat baru berangkat maupun sudah sampai, bagian Kedatangan kosong
     * dulu selama trip belum selesai).
     */
    private function buildWhatsAppLink(OperatorTrip $trip): string
    {
        return 'https://wa.me/?text=' . urlencode($this->buildWhatsAppMessage($trip));
    }

    private function buildWhatsAppMessage(OperatorTrip $trip): string
    {
        $namaHari = [0 => 'MINGGU', 1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS', 5 => 'JUMAT', 6 => 'SABTU'];
        $waktuBerangkat = $trip->waktu_berangkat;
        $hariTanggal = $namaHari[$waktuBerangkat->dayOfWeek] . ' ' . $waktuBerangkat->format('d/m/y');
        $tekanan = rtrim(rtrim(number_format((float) $trip->tekanan, 2, '.', ''), '0'), '.');

        $nama = optional($trip->operator)->nama ?? optional($trip->user)->name;
        $tibaDi = $trip->status === 'selesai' ? $trip->tujuan_nama : '';
        $jamSampai = $trip->waktu_sampai ? $trip->waktu_sampai->format('H:i') : '';

        return "*Keberangkatan*\n"
            . "Nama : {$nama}\n"
            . "GTM Nopol : {$trip->nopol}\n"
            . "Hari/Tgl : {$hariTanggal}\n"
            . "Berangkat Dari : {$trip->asal_nama}\n"
            . "Jam : {$waktuBerangkat->format('H:i')}\n"
            . "Tujuan : {$trip->tujuan_nama}\n"
            . "Tekanan : {$tekanan}\n"
            . "\n"
            . "*Kedatangan*\n"
            . "Tiba Di : {$tibaDi}\n"
            . "Jam : {$jamSampai}";
    }

    /**
     * Gabungan daftar lokasi untuk dropdown Asal/Tujuan: alamat_pengambilan,
     * customer/fob/mmbtu, trip_lokasi yang sudah approved, + trip_lokasi milik
     * driver ini sendiri yang masih pending (hanya valid untuk driver tsb).
     */
    private function getLokasiOptions()
    {
        $options = collect();

        foreach (AlamatPengambilan::orderBy('nama_alamat')->get() as $alamat) {
            $options->push([
                'value' => 'alamat_pengambilan:' . $alamat->id,
                'label' => $alamat->nama_alamat,
                'group' => 'Alamat Pengambilan',
            ]);
        }

        foreach (User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_FOB, User::ROLE_MMBTU])->orderBy('name')->get() as $customer) {
            $options->push([
                'value' => 'customer:' . $customer->id,
                'label' => trim($customer->name . ($customer->alamat ? ' (' . $customer->alamat . ')' : '')),
                'group' => 'Customer / FOB',
            ]);
        }

        foreach (TripLokasi::where('status', 'approved')->orderBy('nama_lokasi')->get() as $lokasi) {
            $options->push([
                'value' => 'trip_lokasi:' . $lokasi->id,
                'label' => $lokasi->nama_lokasi,
                'group' => 'Lokasi Lainnya',
            ]);
        }

        foreach (TripLokasi::where('status', 'pending')->where('diajukan_oleh_user_id', Auth::id())->orderBy('nama_lokasi')->get() as $lokasi) {
            $options->push([
                'value' => 'trip_lokasi:' . $lokasi->id,
                'label' => $lokasi->nama_lokasi . ' (menunggu approval)',
                'group' => 'Lokasi Saya (Pending)',
            ]);
        }

        return $options;
    }

    /**
     * Mengubah value dropdown ("type:id" atau "new") menjadi data lokasi
     * yang siap disimpan di operator_trip (type, id, nama snapshot).
     * Untuk value "new", buat record trip_lokasi baru berstatus pending.
     */
    private function resolveLokasi(string $value, ?string $namaBaru, User $user): array
    {
        if ($value === 'new') {
            $lokasi = TripLokasi::create([
                'nama_lokasi' => $namaBaru,
                'status' => 'pending',
                'diajukan_oleh_user_id' => $user->id,
            ]);

            return ['type' => 'trip_lokasi', 'id' => $lokasi->id, 'nama' => $lokasi->nama_lokasi];
        }

        [$type, $id] = explode(':', $value, 2);

        $nama = match ($type) {
            'alamat_pengambilan' => optional(AlamatPengambilan::find($id))->nama_alamat,
            'customer' => optional(User::find($id))->name,
            'trip_lokasi' => optional(TripLokasi::find($id))->nama_lokasi,
            default => null,
        };

        return ['type' => $type, 'id' => (int) $id, 'nama' => $nama];
    }
}
