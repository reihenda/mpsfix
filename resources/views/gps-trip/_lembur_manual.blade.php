{{-- Hari yang tidak diterapkan otomatis ke lembur karena sudah ada input lembur manual (flash 'lembur_manual'). --}}
@if (session('lembur_manual'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>{{ count(session('lembur_manual')) }} hari tidak diterapkan ke lembur</strong> karena sudah ada data lembur yang diisi manual:
        @foreach (collect(session('lembur_manual'))->groupBy('bulan') as $bulanManual => $daftar)
            {{ $daftar->pluck('label')->implode(', ') }}
            (<a href="{{ route('gps-trip.lembur', ['bulan' => $bulanManual]) }}">bandingkan &amp; pilih mau diganti atau tidak</a>){{ $loop->last ? '' : ';' }}
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button>
    </div>
@endif
