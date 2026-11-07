@extends('layouts.app')

@section('title', 'Pemetaan Cartrack')

@section('page-title', 'Pemetaan Cartrack')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jadwal-perjalanan.css') }}?v={{ filemtime(public_path('css/jadwal-perjalanan.css')) }}">
@endpush

@section('content')
<div class="jp">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @if ($galat)
        <div class="alert alert-danger"><strong>Tidak bisa terhubung ke Cartrack:</strong> {{ $galat }}</div>
    @endif

    <div class="jp-head">
        <p>Cocokkan driver di sistem ini dengan driver di Cartrack. Pemetaan dipakai saat jadwal mengirim driver ke kendaraan.</p>
        <div class="jp-head-actions">
            <a href="{{ route('jadwal-perjalanan.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i>Jadwal perjalanan</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('pemetaan-cartrack.simpan') }}">
                @csrf
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Driver kita ke driver Cartrack</h3></div>
                    <div>
                        @forelse ($operators as $op)
                            @php $dipilih = old("peta.{$op->id}", $op->cartrack_driver_id); @endphp
                            <div class="jp-map-row">
                                <div class="jp-map-name">{{ $op->nama }}</div>
                                <div>
                                    <select name="peta[{{ $op->id }}]" class="form-control" aria-label="Driver Cartrack untuk {{ $op->nama }}">
                                        <option value="">Belum dipetakan</option>
                                        @foreach ($driverCartrack as $id => $d)
                                            <option value="{{ $id }}" @selected($dipilih === $id)>{{ $d['nama'] }}</option>
                                        @endforeach
                                    </select>
                                    @if (!$op->cartrack_driver_id && ($saran[$op->id] ?? null))
                                        <span class="jp-hint-saran">
                                            <i class="fas fa-lightbulb"></i>
                                            Saran: {{ $driverCartrack[$saran[$op->id]]['nama'] }}
                                            <a href="#" class="js-terapkan-saran" data-select="peta[{{ $op->id }}]" data-nilai="{{ $saran[$op->id] }}">Pakai</a>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="jp-empty"><i class="fas fa-user-slash"></i><strong>Belum ada driver</strong>Tambahkan driver di menu Lembur Operator GTM.</div>
                        @endforelse
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" @disabled($galat)>Simpan pemetaan</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Kendaraan</h3>
                    <form method="POST" action="{{ route('pemetaan-cartrack.segarkan') }}" class="m-0">@csrf
                        <button class="btn btn-sm btn-ghost"><i class="fas fa-sync-alt"></i>Segarkan</button></form>
                </div>
                <div class="jp-scroll">
                    <table class="table jp-table">
                        <thead><tr><th>Nopol kita</th><th>Di Cartrack</th></tr></thead>
                        <tbody>
                            @foreach ($kendaraanKita as $k)
                                <tr>
                                    <td class="jp-nopol">{{ $k['nopol'] }}</td>
                                    <td>
                                        @if ($k['registrasi'])
                                            <span class="jp-badge is-ok">{{ $k['registrasi'] }}</span>
                                        @else
                                            <span class="jp-badge is-mute">Tidak ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($kendaraanHanyaDiCartrack->isNotEmpty())
                    <div class="card-footer">
                        <strong class="d-block mb-1" style="font-size:.8rem">Ada di Cartrack, belum ada di Kelola Mobil/NOPOL</strong>
                        <span class="jp-sub">{{ $kendaraanHanyaDiCartrack->implode(', ') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-terapkan-saran').forEach(function (a) {
    a.addEventListener('click', function (e) {
        e.preventDefault();
        const sel = document.querySelector('select[name="' + a.dataset.select + '"]');
        if (sel) { sel.value = a.dataset.nilai; sel.style.borderColor = '#15803d'; }
    });
});
</script>
@endpush
