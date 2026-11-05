@extends('layouts.app')

@section('title', 'Master Geofence GPS')

@section('page-title', 'Master Geofence GPS')

@section('content')
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
            <div class="card-header">
                <h3 class="card-title">Klasifikasi Geofence ({{ $geofence->count() }})</h3>
                <div class="card-tools">
                    <a href="{{ route('gps-trip.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Trip</a>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <div class="callout callout-warning m-3 small">
                    Mengubah klasifikasi tidak langsung mengubah trip yang sudah ada. Setelah mengubah, <strong>upload ulang file laporan</strong>
                    agar trip dihitung ulang (trip yang sudah Anda edit atau sudah diberi driver tidak ditimpa).
                </div>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama Geofence</th>
                            <th style="width: 260px;">Jenis</th>
                            <th style="width: 300px;">Customer terdaftar</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($geofence as $g)
                            <tr>
                                <td class="align-middle">{{ $g->nama }}</td>
                                <td>
                                    <select name="tipe" form="gf-{{ $g->id }}" class="form-control form-control-sm js-tipe">
                                        @foreach (\App\Models\GpsGeofence::TIPE_LABEL as $nilai => $label)
                                            <option value="{{ $nilai }}" @selected($g->tipe === $nilai)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="customer_id" form="gf-{{ $g->id }}" class="form-control form-control-sm js-customer">
                                        <option value="">— tidak ditautkan —</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" @selected($g->customer_id === $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-right">
                                    <form id="gf-{{ $g->id }}" action="{{ route('gps-trip.geofence.update', $g->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada geofence. Upload laporan GPS terlebih dahulu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-tipe').forEach(function (sel) {
        function sinkron() {
            var cust = sel.closest('tr').querySelector('.js-customer');
            cust.disabled = sel.value !== 'customer';
        }
        sel.addEventListener('change', sinkron);
        sinkron();
    });
</script>
@endpush
