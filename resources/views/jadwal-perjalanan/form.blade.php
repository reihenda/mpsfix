@extends('layouts.app')

@section('title', $jadwal->exists ? 'Ubah Jadwal Perjalanan' : 'Buat Jadwal Perjalanan')

@section('page-title', $jadwal->exists ? 'Ubah Jadwal Perjalanan' : 'Buat Jadwal Perjalanan')

@section('content')
@php
    use App\Services\GpsTrip\GpsTripImporter;
    $ruteLama = old('rute', $rute);
@endphp

<div class="row">
    <div class="col-lg-8">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($terkunci)
            <div class="alert alert-info">
                Jadwal sedang berjalan (driver sudah di-link di Cartrack). Yang bisa diubah: waktu selesai, rute, dan catatan.
                Untuk mengganti driver atau kendaraan, batalkan jadwal ini lalu buat yang baru.
            </div>
        @endif

        @if ($kendaraanCartrack === null)
            <div class="alert alert-warning py-2">Daftar kendaraan Cartrack tidak bisa dimuat sekarang; form tetap bisa dipakai.</div>
        @endif

        <form method="POST" action="{{ $jadwal->exists ? route('jadwal-perjalanan.update', $jadwal) : route('jadwal-perjalanan.store') }}">
            @csrf
            @if ($jadwal->exists) @method('PUT') @endif

            <div class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nopol">Kendaraan</label>
                            <select name="nopol" id="nopol" class="form-control" required @disabled($terkunci)>
                                <option value="">— pilih nopol —</option>
                                @foreach ($daftarNopol as $n)
                                    @php $ada = $kendaraanCartrack === null || in_array(GpsTripImporter::kunciNopol($n), $kendaraanCartrack, true); @endphp
                                    <option value="{{ $n }}" @selected(old('nopol', $jadwal->nopol) === $n)>
                                        {{ $n }}{{ $ada ? '' : ' (tidak ada di Cartrack)' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($terkunci) <input type="hidden" name="nopol" value="{{ $jadwal->nopol }}"> @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label for="operator_gtm_id">Driver</label>
                            <select name="operator_gtm_id" id="operator_gtm_id" class="form-control" required @disabled($terkunci)>
                                <option value="">— pilih driver —</option>
                                @foreach ($operators as $o)
                                    <option value="{{ $o->id }}" @selected((int) old('operator_gtm_id', $jadwal->operator_gtm_id) === $o->id)>
                                        {{ $o->nama }}{{ $o->cartrack_driver_id ? '' : ' (belum dipetakan ke Cartrack)' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($terkunci) <input type="hidden" name="operator_gtm_id" value="{{ $jadwal->operator_gtm_id }}"> @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="waktu_mulai">Mulai</label>
                            <input type="datetime-local" name="waktu_mulai" id="waktu_mulai" class="form-control" required
                                   value="{{ old('waktu_mulai', $jadwal->waktu_mulai?->format('Y-m-d\TH:i')) }}" @readonly($terkunci)>
                            <small class="text-muted">Driver dikirim ke Cartrack saat waktu ini tiba.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="waktu_selesai">Selesai</label>
                            <input type="datetime-local" name="waktu_selesai" id="waktu_selesai" class="form-control" required
                                   value="{{ old('waktu_selesai', $jadwal->waktu_selesai?->format('Y-m-d\TH:i')) }}">
                            <small class="text-muted">Setelah ini lewat, driver dilepas dan trip ditarik dari Cartrack.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rute (urutan tujuan)</label>
                        <datalist id="daftar-tujuan">
                            @foreach ($daftarTujuan as $t) <option value="{{ $t }}"> @endforeach
                        </datalist>
                        <div id="rute-baris">
                            @foreach ($ruteLama as $i => $r)
                                <div class="input-group mb-2 rute-item">
                                    <div class="input-group-prepend"><span class="input-group-text rute-no">{{ $i + 1 }}</span></div>
                                    <input type="text" name="rute[{{ $i }}][tujuan]" class="form-control" list="daftar-tujuan"
                                           placeholder="Tujuan (customer / SPBG / lokasi)" value="{{ $r['tujuan'] ?? '' }}">
                                    <input type="text" name="rute[{{ $i }}][catatan]" class="form-control" placeholder="Catatan (opsional)"
                                           value="{{ $r['catatan'] ?? '' }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger js-hapus-rute" title="Hapus">&times;</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="tambah-rute"><i class="fas fa-plus"></i> Tambah tujuan</button>
                    </div>

                    <div class="form-group mb-0">
                        <label for="catatan">Catatan</label>
                        <textarea name="catatan" id="catatan" rows="2" class="form-control">{{ old('catatan', $jadwal->catatan) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Simpan Jadwal</button>
                    <a href="{{ $jadwal->exists ? route('jadwal-perjalanan.show', $jadwal) : route('jadwal-perjalanan.index') }}" class="btn btn-default">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
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
