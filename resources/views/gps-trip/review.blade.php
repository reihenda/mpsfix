@extends('layouts.app')

@section('title', 'Klasifikasi Geofence')

@section('page-title', 'Klasifikasi Geofence Baru')

@section('content')
<div class="row">
    <div class="col-12">
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="alert alert-info">
            <strong>{{ $namaFile }}</strong> terbaca: {{ $jumlahKendaraan }} kendaraan, {{ $jumlahSegmen }} baris perjalanan
            @if ($periode[0]) (periode {{ \Carbon\Carbon::parse($periode[0])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($periode[1])->format('d/m/Y') }}) @endif.
            <br>
            Ada <strong>{{ count($geofenceBaru) }}</strong> nama geofence yang belum pernah dikenal. Tentukan mana yang merupakan
            <strong>customer/PT</strong> (menjadi awal/akhir trip) dan mana yang hanya titik singgah (SPBG, workshop, dll).
            Pilihan ini disimpan, jadi upload bulan berikutnya tidak akan ditanyakan lagi.
        </div>

        <form action="{{ route('gps-trip.review.konfirmasi', $token) }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama Geofence (dari GPS)</th>
                                <th class="text-right" style="width: 90px;">Muncul</th>
                                <th style="width: 260px;">Jenis</th>
                                <th style="width: 300px;">Customer terdaftar (opsional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($geofenceBaru as $i => $g)
                                <tr>
                                    <td>
                                        <strong>{{ $g['nama'] }}</strong>
                                        <input type="hidden" name="geofence[{{ $i }}][nama]" value="{{ $g['nama'] }}">
                                    </td>
                                    <td class="text-right">{{ $g['jumlah'] }}×</td>
                                    <td>
                                        <select name="geofence[{{ $i }}][tipe]" class="form-control form-control-sm js-tipe">
                                            @foreach (\App\Models\GpsGeofence::TIPE_LABEL as $nilai => $label)
                                                <option value="{{ $nilai }}" @selected(old("geofence.$i.tipe", $g['tipe']) === $nilai)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="geofence[{{ $i }}][customer_id]" class="form-control form-control-sm js-customer">
                                            <option value="">— tidak ditautkan —</option>
                                            @foreach ($customers as $c)
                                                <option value="{{ $c->id }}" @selected((int) old("geofence.$i.customer_id", $g['customer_id']) === $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        @if ($g['customer_id'])
                                            <small class="text-success">Cocok otomatis dengan nama customer.</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Simpan &amp; Import Trip</button>
                    <a href="{{ route('gps-trip.upload') }}" class="btn btn-secondary">Batal</a>
                    <small class="text-muted ml-2">Customer terdaftar hanya untuk label; yang menentukan trip adalah kolom "Jenis".</small>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Pilihan customer hanya relevan untuk jenis "customer"
    document.querySelectorAll('.js-tipe').forEach(function (sel) {
        function sinkron() {
            var cust = sel.closest('tr').querySelector('.js-customer');
            cust.disabled = sel.value !== 'customer';
            if (cust.disabled) { cust.value = ''; }
        }
        sel.addEventListener('change', sinkron);
        sinkron();
    });
</script>
@endpush
