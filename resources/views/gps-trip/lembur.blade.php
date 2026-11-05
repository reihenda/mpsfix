@extends('layouts.app')

@section('title', 'Terapkan Trip GPS ke Lembur')

@section('page-title', 'Terapkan Trip GPS ke Lembur Operator')

@section('css')
<style>
    .lembur-table td { vertical-align: top; }
    .sesi-chip {
        display: inline-block; background: #e9f2ff; color: #0b4a99; border-radius: .25rem;
        padding: .05rem .4rem; margin: 0 .2rem .2rem 0; font-size: .82rem; white-space: nowrap;
    }
    .sesi-chip.lama { background: #fff3cd; color: #7a5b00; }
    .baris-sama { color: #8a8f98; }
</style>
@endsection

@section('content')
@php
    $fmt = fn (int $menit) => sprintf('%d:%02d', intdiv($menit, 60), $menit % 60);
    $labelStatus = [
        'baru' => ['success', 'Baru'],
        'berbeda' => ['warning', 'Berbeda dari data lembur yang ada'],
        'sama' => ['secondary', 'Sudah sesuai'],
    ];
@endphp

<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-body py-2">
                <form action="{{ route('gps-trip.lembur') }}" method="GET" class="form-inline">
                    <label class="mr-2 small text-muted">Bulan</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <a href="{{ route('gps-trip.index', ['bulan' => $bulan]) }}" class="btn btn-secondary btn-sm ml-auto"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Trip GPS</a>
                </form>
            </div>
        </div>

        <div class="callout callout-info small">
            <strong>Cara kerja:</strong> tiap trip yang <em>dihitung</em> dan sudah punya driver menjadi satu sesi lembur driver itu di tanggal trip dimulai
            (jam masuk = mulai trip, jam keluar = selesai trip). Jeda antar sesi tidak dihitung. Trip yang bertumpuk jamnya digabung, dan
            bila lebih dari 5 trip dalam sehari, trip dengan jeda terkecil digabung agar muat 5 sesi. Jeda lebih dari 6 jam di dalam satu trip
            (mobil parkir di titik tak terbaca) dipecah dan tidak dihitung sebagai jam kerja.
            Jam kerja normal dan tarif lembur mengikuti pengaturan operator.
        </div>

        @if ($tanpaDriver > 0)
            <div class="alert alert-warning py-2">
                <i class="fas fa-user-slash mr-1"></i>
                <strong>{{ $tanpaDriver }} trip</strong> di bulan ini belum punya driver, jadi belum masuk hitungan.
                <a href="{{ route('gps-trip.index', ['bulan' => $bulan, 'driver' => 'kosong', 'tampil' => 'dihitung']) }}">Lengkapi driver</a>
            </div>
        @endif

        @if (empty($baris))
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    Belum ada trip yang dihitung dan sudah punya driver di bulan ini.
                </div>
            </div>
        @else
            <form action="{{ route('gps-trip.lembur.terapkan') }}" method="POST" id="form-lembur">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ count($baris) }} hari lembur:
                            <span class="badge badge-success">{{ $ringkasan['baru'] }} baru</span>
                            <span class="badge badge-warning">{{ $ringkasan['berbeda'] }} berbeda</span>
                            <span class="badge badge-secondary">{{ $ringkasan['sama'] }} sudah sesuai</span>
                        </h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover lembur-table mb-0" style="min-width: 860px;">
                            <thead>
                                <tr>
                                    <th style="width: 32px;"><input type="checkbox" id="pilih-semua" title="Pilih semua yang bisa diterapkan"></th>
                                    <th>Driver</th>
                                    <th>Tanggal</th>
                                    <th>Kendaraan</th>
                                    <th>Sesi</th>
                                    <th class="text-right">Jam kerja</th>
                                    <th class="text-right">Lembur</th>
                                    <th class="text-right">Upah lembur</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($baris as $b)
                                    <tr class="{{ $b['status'] === 'sama' ? 'baris-sama' : '' }}">
                                        <td>
                                            <input type="checkbox" name="pilih[]" value="{{ $b['kunci'] }}"
                                                   class="js-pilih" data-status="{{ $b['status'] }}"
                                                   @checked($b['status'] === 'baru') @disabled($b['status'] === 'sama')>
                                        </td>
                                        <td>
                                            <a href="{{ route('operator-gtm.show', $b['operator_id']) }}">{{ $b['operator'] }}</a>
                                        </td>
                                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($b['tanggal'])->locale('id')->translatedFormat('D, d M Y') }}</td>
                                        <td>{{ implode(', ', $b['nopol']) }}</td>
                                        <td>
                                            @foreach ($b['sesi'] as $i => $s)
                                                <span class="sesi-chip">{{ $i + 1 }}. {{ $s['masuk'] }}–{{ $s['keluar'] }}</span>
                                            @endforeach
                                            @if ($b['status'] === 'berbeda' && $b['existing'])
                                                <div class="small text-muted mt-1">Saat ini di data lembur:</div>
                                                @forelse ($b['existing']['sesi'] as $i => $s)
                                                    <span class="sesi-chip lama">{{ $i + 1 }}. {{ $s['masuk'] }}–{{ $s['keluar'] }}</span>
                                                @empty
                                                    <span class="small text-muted">(tanpa sesi)</span>
                                                @endforelse
                                                <div class="small text-muted">
                                                    {{ $fmt($b['existing']['total_menit']) }} jam kerja, lembur {{ $fmt($b['existing']['lembur_menit']) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right text-nowrap">{{ $fmt($b['total_menit']) }}</td>
                                        <td class="text-right text-nowrap">{{ $fmt($b['lembur_menit']) }}</td>
                                        <td class="text-right text-nowrap">Rp {{ number_format($b['upah'], 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $labelStatus[$b['status']][0] }}">{{ $labelStatus[$b['status']][1] }}</span>
                                            @if ($b['status'] === 'berbeda')
                                                <div class="small text-muted mt-1">Bila dicentang, sesi lama akan <strong>diganti</strong>.</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        <button type="submit" class="btn btn-primary" id="btn-terapkan"><i class="fas fa-check mr-1"></i> Terapkan ke Lembur (<span id="jumlah-pilih">0</span>)</button>
                        <small class="text-muted ml-3">Hari yang sudah ada datanya dan berbeda tidak dicentang otomatis, supaya input manual tidak tertimpa tanpa sengaja.</small>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var boxes = document.querySelectorAll('.js-pilih:not(:disabled)');
    var jumlah = document.getElementById('jumlah-pilih');
    var semua = document.getElementById('pilih-semua');
    function hitung() {
        var n = document.querySelectorAll('.js-pilih:checked').length;
        if (jumlah) { jumlah.textContent = n; }
        var btn = document.getElementById('btn-terapkan');
        if (btn) { btn.disabled = n === 0; }
    }
    boxes.forEach(function (b) { b.addEventListener('change', hitung); });
    if (semua) {
        semua.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = semua.checked; });
            hitung();
        });
    }
    var form = document.getElementById('form-lembur');
    if (form) {
        form.addEventListener('submit', function (e) {
            var timpa = document.querySelectorAll('.js-pilih[data-status="berbeda"]:checked').length;
            if (timpa > 0 && !confirm(timpa + ' hari akan MENGGANTI sesi lembur yang sudah ada dengan data GPS. Lanjutkan?')) {
                e.preventDefault();
            }
        });
    }
    hitung();
})();
</script>
@endpush
