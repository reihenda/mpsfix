<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OperatorGtmLembur extends Model
{
    use HasFactory;

    protected $table = 'operator_gtm_lembur';

    protected $fillable = [
        'operator_gtm_id',
        'tanggal',
        'jam_masuk_sesi_1',
        'jam_keluar_sesi_1',
        'jam_masuk_sesi_2',
        'jam_keluar_sesi_2',
        'jam_masuk_sesi_3',
        'jam_keluar_sesi_3',
        'jam_masuk_sesi_4',
        'jam_keluar_sesi_4',
        'jam_masuk_sesi_5',
        'jam_keluar_sesi_5',
        'total_jam_kerja',
        'total_jam_lembur',
        'upah_lembur',
    ];

    /**
     * Mendapatkan operator yang terkait dengan record lembur ini
     */
    public function operator()
    {
        return $this->belongsTo(OperatorGtm::class, 'operator_gtm_id');
    }

    /**
     * Menghitung durasi satu sesi (dalam menit) dari jam masuk & jam keluar (format H:i)
     */
    public static function calculateSessionDuration($jamMasuk, $jamKeluar)
    {
        $today = Carbon::today();

        $masuk = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
        $keluar = Carbon::parse($today->format('Y-m-d') . ' ' . $jamKeluar);

        // Jika keluar lebih kecil dari masuk, artinya melewati tengah malam
        if ($keluar->lt($masuk)) {
            $keluar->addDay();
        }

        return $masuk->diffInMinutes($keluar);
    }

    /**
     * Menghitung total jam kerja (menit) dari data sesi 1-5.
     * $sesiData: array asosiatif berisi key jam_masuk_sesi_{1..5} / jam_keluar_sesi_{1..5}
     */
    public static function calculateTotalWorkingHours(array $sesiData)
    {
        $totalJamKerja = 0;

        for ($sesi = 1; $sesi <= 5; $sesi++) {
            $jamMasuk = $sesiData["jam_masuk_sesi_{$sesi}"] ?? null;
            $jamKeluar = $sesiData["jam_keluar_sesi_{$sesi}"] ?? null;

            if ($jamMasuk && $jamKeluar) {
                $durasi = self::calculateSessionDuration($jamMasuk, $jamKeluar);
                $totalJamKerja += $durasi;
                \Log::info("Durasi sesi {$sesi}: {$durasi} menit");
            }
        }

        return $totalJamKerja;
    }

    /**
     * Memfilter sesi yang tidak lengkap (hanya salah satu dari jam masuk/keluar terisi)
     * supaya tidak ikut disimpan ke database.
     */
    public static function filterEmptySessions(array $data)
    {
        $filteredData = [];

        foreach ($data as $key => $value) {
            if (!str_contains($key, 'jam_masuk_sesi_') && !str_contains($key, 'jam_keluar_sesi_')) {
                $filteredData[$key] = $value;
            }
        }

        for ($sesi = 1; $sesi <= 5; $sesi++) {
            $jamMasuk = $data["jam_masuk_sesi_{$sesi}"] ?? null;
            $jamKeluar = $data["jam_keluar_sesi_{$sesi}"] ?? null;

            if ($jamMasuk && $jamKeluar) {
                $filteredData["jam_masuk_sesi_{$sesi}"] = $jamMasuk;
                $filteredData["jam_keluar_sesi_{$sesi}"] = $jamKeluar;
            }
        }

        return $filteredData;
    }

    /**
     * Menghitung jam lembur (menit) & upah lembur dari total jam kerja dan jam kerja normal operator.
     * Mengembalikan ['total_jam_lembur' => int menit, 'upah_lembur' => float].
     */
    public static function calculateUpahLembur(int $totalJamKerja, ?int $jamKerjaOperator)
    {
        $jamKerjaMenit = ($jamKerjaOperator ?: 8) * 60;
        $jamLembur = max(0, $totalJamKerja - $jamKerjaMenit);

        $upahPerJam = KonfigurasiLembur::getTarifLembur();
        $upahLembur = ($jamLembur / 60) * $upahPerJam;

        return [
            'total_jam_lembur' => $jamLembur,
            'upah_lembur' => $upahLembur,
        ];
    }
}