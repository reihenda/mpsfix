<?php

namespace App\Services;

use App\Mail\SaldoMinimumHarian;
use App\Models\PengaturanSistem;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email notifikasi customer Rupiah yang saldonya di bawah minimum.
 * Dipakai oleh jadwal harian (09.00 WIB) dan tombol "Kirim tes sekarang".
 */
class NotifikasiSaldoService
{
    /** @return array{di_bawah: \Illuminate\Support\Collection, belum_diatur: \Illuminate\Support\Collection} */
    public function kondisi(): array
    {
        $customers = User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get();
        $diBawah = collect();
        $belumDiatur = collect();

        foreach ($customers as $c) {
            $s = $c->statusSaldoMinimum();
            if ($s['status'] === 'di_bawah') {
                $diBawah->push([
                    'nama' => $c->name,
                    'saldo' => $s['saldo'],
                    'minimum' => $s['minimum'],
                    'kurang' => abs($s['selisih']),
                    'url' => route('data-pencatatan.customer-detail', $c->id),
                ]);
            } elseif ($s['status'] === 'belum_diatur') {
                $belumDiatur->push($c->name);
            }
        }

        return ['di_bawah' => $diBawah->sortBy('saldo')->values(), 'belum_diatur' => $belumDiatur];
    }

    /**
     * @param bool $tes true = kirim walau tidak ada yang di bawah minimum & walau notifikasi nonaktif
     * @return array{terkirim: bool, pesan: string}
     */
    public function kirim(bool $tes = false): array
    {
        $penerima = PengaturanSistem::penerimaNotifSaldo();
        if (!$penerima) {
            return ['terkirim' => false, 'pesan' => 'Belum ada email penerima yang valid.'];
        }
        if (!$tes && !PengaturanSistem::notifSaldoAktif()) {
            return ['terkirim' => false, 'pesan' => 'Notifikasi email sedang nonaktif.'];
        }

        $k = $this->kondisi();
        if (!$tes && $k['di_bawah']->isEmpty()) {
            return ['terkirim' => false, 'pesan' => 'Tidak ada customer di bawah minimum; email tidak dikirim.'];
        }

        $tanggal = now('Asia/Jakarta')->locale('id')->translatedFormat('j F Y');
        try {
            Mail::to($penerima)->send(new SaldoMinimumHarian($k['di_bawah'], $k['belum_diatur'], $tanggal, $tes));
        } catch (\Throwable $e) {
            Log::error('Notifikasi saldo minimum gagal dikirim: ' . $e->getMessage());
            return ['terkirim' => false, 'pesan' => 'Gagal mengirim email: ' . $e->getMessage()];
        }

        Log::info('Notifikasi saldo minimum terkirim', ['penerima' => $penerima, 'di_bawah' => $k['di_bawah']->count(), 'tes' => $tes]);
        return ['terkirim' => true, 'pesan' => 'Email terkirim ke ' . implode(', ', $penerima) . '.'];
    }
}
