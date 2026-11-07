@extends('layouts.app')

@section('title', 'Pemetaan Cartrack')

@section('page-title', 'Pemetaan Cartrack')

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @if ($galat)
            <div class="alert alert-danger"><strong>Tidak bisa terhubung ke Cartrack:</strong> {{ $galat }}</div>
        @endif
    </div>

    <div class="col-lg-7">
        <form method="POST" action="{{ route('pemetaan-cartrack.simpan') }}">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Driver kita → driver Cartrack</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Driver (sistem kita)</th><th>Driver di Cartrack</th></tr></thead>
                        <tbody>
                            @foreach ($operators as $op)
                                @php $dipilih = old("peta.{$op->id}", $op->cartrack_driver_id); @endphp
                                <tr>
                                    <td class="align-middle">{{ $op->nama }}</td>
                                    <td>
                                        <select name="peta[{{ $op->id }}]" class="form-control form-control-sm">
                                            <option value="">— belum dipetakan —</option>
                                            @foreach ($driverCartrack as $id => $d)
                                                <option value="{{ $id }}" @selected($dipilih === $id)>{{ $d['nama'] }}</option>
                                            @endforeach
                                        </select>
                                        @if (!$op->cartrack_driver_id && ($saran[$op->id] ?? null))
                                            <small class="text-info">
                                                Saran: {{ $driverCartrack[$saran[$op->id]]['nama'] }}
                                                <a href="#" class="js-terapkan-saran" data-select="peta[{{ $op->id }}]" data-nilai="{{ $saran[$op->id] }}">pakai</a>
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary" @disabled($galat)>Simpan Pemetaan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Kendaraan</h3>
                <div class="card-tools">
                    <form method="POST" action="{{ route('pemetaan-cartrack.segarkan') }}">@csrf
                        <button class="btn btn-xs btn-outline-secondary">Segarkan data Cartrack</button></form>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 480px;">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Nopol kita</th><th>Di Cartrack</th></tr></thead>
                    <tbody>
                        @foreach ($kendaraanKita as $k)
                            <tr>
                                <td>{{ $k['nopol'] }}</td>
                                <td>
                                    @if ($k['registrasi'])
                                        <span class="text-success"><i class="fas fa-check"></i> {{ $k['registrasi'] }}</span>
                                    @else
                                        <span class="text-muted">tidak ada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($kendaraanHanyaDiCartrack->isNotEmpty())
                <div class="card-footer small">
                    <strong>Ada di Cartrack, belum ada di Kelola Mobil/NOPOL:</strong>
                    {{ $kendaraanHanyaDiCartrack->implode(', ') }}
                </div>
            @endif
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
        if (sel) { sel.value = a.dataset.nilai; sel.classList.add('border-success'); }
    });
});
</script>
@endpush
