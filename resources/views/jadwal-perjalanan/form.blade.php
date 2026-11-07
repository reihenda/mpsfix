@extends('layouts.app')

@section('title', $jadwal->exists ? 'Ubah Jadwal Perjalanan' : 'Buat Jadwal Perjalanan')

@section('page-title', $jadwal->exists ? 'Ubah Jadwal Perjalanan' : 'Buat Jadwal Perjalanan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jadwal-perjalanan.css') }}?v={{ filemtime(public_path('css/jadwal-perjalanan.css')) }}">
@endpush

@section('content')
@php
    $ruteLama = old('rute', $rute);
    $bisaKelolaNopol = in_array(auth()->user()->role, ['admin', 'superadmin', 'staff'], true);
@endphp

<div class="jp">
<div class="row">
    <div class="col-xl-9">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($terkunci)
            <div class="alert alert-info">
                Jadwal sedang berjalan dan driver sudah di-link di Cartrack. Yang bisa diubah: waktu selesai, rute, dan catatan.
                Untuk mengganti driver atau kendaraan, batalkan jadwal ini lalu buat yang baru.
            </div>
        @endif

        @if (!$cartrackMuat)
            <div class="alert alert-warning py-2">Data Cartrack tidak bisa dimuat sekarang, jadi semua kendaraan dan driver ditampilkan tanpa penyaringan. Form tetap bisa dipakai.</div>
        @endif

        <form method="POST" action="{{ $jadwal->exists ? route('jadwal-perjalanan.update', $jadwal) : route('jadwal-perjalanan.store') }}">
            @csrf
            @if ($jadwal->exists) @method('PUT') @endif

            <div class="card">
                <div class="card-body">
                    <h4 class="jp-section-title">Kendaraan dan driver</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nopol">Kendaraan</label>
                            <select name="nopol" id="nopol" class="form-control" required @disabled($terkunci)>
                                <option value="">Pilih nopol</option>
                                @foreach ($daftarNopol as $n)
                                    <option value="{{ $n }}" @selected(old('nopol', $jadwal->nopol) === $n)>{{ $n }}</option>
                                @endforeach
                                @if ($cartrackMuat && !$terkunci)
                                    <option value="__lainnya">Nopol saya tidak ada di daftar…</option>
                                @endif
                            </select>
                            @if ($terkunci) <input type="hidden" name="nopol" value="{{ $jadwal->nopol }}"> @endif
                            <span class="form-hint">Hanya kendaraan yang sudah terhubung antara Cartrack dan sistem ini.</span>

                            @if ($cartrackMuat)
                                <div class="jp-callout" id="info-nopol" @if ($daftarNopol->isNotEmpty()) hidden @endif>
                                    <strong>Kenapa nopol saya tidak muncul?</strong>
                                    <p>Nopol baru muncul di sini kalau ada di Cartrack <em>dan</em> di Kelola Mobil/NOPOL. Yang belum terhubung:</p>
                                    @if ($nopolBelumTerhubung->isNotEmpty())
                                        <p class="jp-callout-label">Ada di sistem kita, tidak ada di Cartrack ({{ $nopolBelumTerhubung->count() }})</p>
                                        <div class="jp-chips">@foreach ($nopolBelumTerhubung as $n)<span class="jp-chip">{{ $n }}</span>@endforeach</div>
                                        <p class="jp-callout-hint">Nopol ini belum didaftarkan sebagai kendaraan di akun Cartrack, atau penulisannya berbeda.</p>
                                    @endif
                                    @if ($nopolHanyaCartrack->isNotEmpty())
                                        <p class="jp-callout-label">Ada di Cartrack, belum ada di sistem kita ({{ $nopolHanyaCartrack->count() }})</p>
                                        <div class="jp-chips">@foreach ($nopolHanyaCartrack as $n)<span class="jp-chip">{{ $n }}</span>@endforeach</div>
                                        <p class="jp-callout-hint">
                                            @if ($bisaKelolaNopol)
                                                Tambahkan di <a href="{{ route('nomor-polisi.index') }}">Kelola Mobil/NOPOL</a>.
                                            @else
                                                Minta admin menambahkannya di Kelola Mobil/NOPOL.
                                            @endif
                                        </p>
                                    @endif
                                    @if ($nopolBelumTerhubung->isEmpty() && $nopolHanyaCartrack->isEmpty())
                                        <p class="mb-0">Semua kendaraan sudah terhubung.</p>
                                    @endif
                                    <a href="{{ route('pemetaan-cartrack.index') }}" class="jp-callout-link">Buka Pemetaan Cartrack <i class="fas fa-arrow-right"></i></a>
                                </div>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label for="operator_gtm_id">Driver</label>
                            <select name="operator_gtm_id" id="operator_gtm_id" class="form-control" required @disabled($terkunci)>
                                <option value="">Pilih driver</option>
                                @foreach ($operators as $o)
                                    <option value="{{ $o->id }}" @selected((int) old('operator_gtm_id', $jadwal->operator_gtm_id) === $o->id)>{{ $o->nama }}</option>
                                @endforeach
                                @if ($cartrackMuat && !$terkunci)
                                    <option value="__lainnya">Driver saya tidak ada di daftar…</option>
                                @endif
                            </select>
                            @if ($terkunci) <input type="hidden" name="operator_gtm_id" value="{{ $jadwal->operator_gtm_id }}"> @endif
                            <span class="form-hint">Hanya driver yang sudah dipetakan ke driver Cartrack.</span>

                            @if ($cartrackMuat)
                                <div class="jp-callout" id="info-driver" @if ($operators->isNotEmpty()) hidden @endif>
                                    <strong>Kenapa driver saya tidak muncul?</strong>
                                    <p>Driver baru muncul di sini kalau sudah dipetakan ke driver Cartrack, karena itulah yang dikirim ke kendaraan saat jadwal mulai.</p>
                                    @if ($operatorBelumTerhubung->isNotEmpty())
                                        <p class="jp-callout-label">Belum dipetakan ({{ $operatorBelumTerhubung->count() }})</p>
                                        <div class="jp-chips">@foreach ($operatorBelumTerhubung as $o)<span class="jp-chip">{{ $o->nama }}</span>@endforeach</div>
                                    @else
                                        <p class="mb-0">Semua driver sudah dipetakan.</p>
                                    @endif
                                    <a href="{{ route('pemetaan-cartrack.index') }}" class="jp-callout-link">Petakan di Pemetaan Cartrack <i class="fas fa-arrow-right"></i></a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="jp-divider">

                    <h4 class="jp-section-title">Waktu</h4>
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-md-0">
                            <label for="waktu_mulai">Mulai</label>
                            <input type="datetime-local" name="waktu_mulai" id="waktu_mulai" class="form-control" required
                                   value="{{ old('waktu_mulai', $jadwal->waktu_mulai?->format('Y-m-d\TH:i')) }}" @readonly($terkunci)>
                            <span class="form-hint">Driver dikirim ke Cartrack saat waktu ini tiba.</span>
                        </div>
                        <div class="form-group col-md-6 mb-0">
                            <label for="waktu_selesai">Selesai</label>
                            <input type="datetime-local" name="waktu_selesai" id="waktu_selesai" class="form-control" required
                                   value="{{ old('waktu_selesai', $jadwal->waktu_selesai?->format('Y-m-d\TH:i')) }}">
                            <span class="form-hint">Setelah lewat, driver dilepas dan trip ditarik dari Cartrack.</span>
                        </div>
                    </div>

                    <hr class="jp-divider">

                    <h4 class="jp-section-title">Rute</h4>
                    <datalist id="daftar-tujuan">
                        @foreach ($daftarTujuan as $t) <option value="{{ $t }}"> @endforeach
                    </datalist>
                    <div id="rute-baris">
                        @foreach ($ruteLama as $i => $r)
                            <div class="jp-rute-item rute-item">
                                <span class="jp-rute-no rute-no">{{ $i + 1 }}</span>
                                <input type="text" name="rute[{{ $i }}][tujuan]" class="form-control" list="daftar-tujuan"
                                       placeholder="Tujuan: customer, SPBG, atau lokasi" aria-label="Tujuan" value="{{ $r['tujuan'] ?? '' }}">
                                <input type="text" name="rute[{{ $i }}][catatan]" class="form-control" placeholder="Catatan (opsional)"
                                       aria-label="Catatan tujuan" value="{{ $r['catatan'] ?? '' }}">
                                <button type="button" class="btn btn-outline-danger js-hapus-rute" title="Hapus tujuan" aria-label="Hapus tujuan">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-ghost mt-1" id="tambah-rute"><i class="fas fa-plus"></i>Tambah tujuan</button>

                    <hr class="jp-divider">

                    <div class="form-group mb-0">
                        <label for="catatan">Catatan jadwal</label>
                        <textarea name="catatan" id="catatan" rows="2" class="form-control" placeholder="Opsional">{{ old('catatan', $jadwal->catatan) }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap" style="gap:.5rem">
                    <button class="btn btn-primary">Simpan jadwal</button>
                    <a href="{{ $jadwal->exists ? route('jadwal-perjalanan.show', $jadwal) : route('jadwal-perjalanan.index') }}" class="btn btn-ghost">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Opsi terakhir "tidak ada di daftar" bukan pilihan sungguhan: tampilkan penjelasan lalu kosongkan pilihan
    [['nopol', 'info-nopol'], ['operator_gtm_id', 'info-driver']].forEach(function (p) {
        const sel = document.getElementById(p[0]);
        const info = document.getElementById(p[1]);
        if (!sel || !info) return;
        sel.addEventListener('change', function () {
            if (sel.value === '__lainnya') {
                sel.value = '';
                info.hidden = false;
                info.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    });

    const wadah = document.getElementById('rute-baris');
    function nomori() {
        wadah.querySelectorAll('.rute-item').forEach(function (el, i) {
            el.querySelector('.rute-no').textContent = i + 1;
            el.querySelectorAll('input').forEach(function (inp) {
                inp.name = inp.name.replace(/rute\[\d+\]/, 'rute[' + i + ']');
            });
        });
    }
    document.getElementById('tambah-rute').addEventListener('click', function () {
        const baru = wadah.querySelector('.rute-item').cloneNode(true);
        baru.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        wadah.appendChild(baru);
        nomori();
        baru.querySelector('input').focus();
    });
    wadah.addEventListener('click', function (e) {
        if (!e.target.classList.contains('js-hapus-rute')) return;
        if (wadah.querySelectorAll('.rute-item').length > 1) {
            e.target.closest('.rute-item').remove();
        } else {
            e.target.closest('.rute-item').querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        }
        nomori();
    });
})();
</script>
@endpush
