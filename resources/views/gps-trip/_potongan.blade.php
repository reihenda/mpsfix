{{-- Berhenti lama di luar geofence. Param: $henti (GpsTrip::hentiDenganBukti()), opsional $nopol.
     Terbukti (ada foto) = jam kerja dipotong; belum terbukti = jam kerja TIDAK dipotong, menunggu foto bukti. --}}
@foreach ($henti as $h)
    @php $ok = !empty($h['terbukti']); @endphp
    <div style="font-size:.78rem;line-height:1.35;border-radius:4px;padding:.25rem .45rem;margin-top:.3rem;max-width:270px;{{ $ok ? 'color:#8a1c1c;background:#fdecec;' : 'color:#7a5b00;background:#fff3cd;' }}">
        <i class="fas {{ $ok ? 'fa-cut' : 'fa-camera' }}"></i>
        @if ($ok)
            <strong>Dipotong {{ \App\Models\GpsTrip::formatDurasi($h['durasi']) }} j</strong>
        @else
            <strong>Berhenti {{ \App\Models\GpsTrip::formatDurasi($h['durasi']) }} j, belum dipotong</strong>
        @endif
        · {{ \Carbon\Carbon::parse($h['tanggal'])->format('d/m') }} {{ $h['jam_mulai'] }}–{{ $h['jam_selesai'] }}
        @if (!empty($h['nopol'] ?? $nopol ?? null)) · {{ $h['nopol'] ?? $nopol }} @endif
        <br>
        <span class="text-muted">Berhenti di {{ $h['lokasi'] !== '' ? $h['lokasi'] : 'luar geofence' }}</span>
        @if (!empty($h['peta']))
            · <a href="{{ $h['peta'] }}" target="_blank" rel="noopener">lihat di peta</a>
        @endif
        <br>
        @if ($ok)
            <span class="text-muted">Bukti: {{ $h['bukti'] }} foto</span>
        @else
            <span>Belum ada foto bukti, jam kerja tetap dihitung penuh.</span>
        @endif
    </div>
@endforeach
