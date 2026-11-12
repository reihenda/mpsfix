{{-- Bukti jam kerja yang dipotong: berhenti > 1 jam di titik yang sama di luar geofence. Param: $henti (GpsTrip::hentiDiJalan()), opsional $nopol --}}
@foreach ($henti as $h)
    <div style="font-size:.78rem;line-height:1.35;color:#8a1c1c;background:#fdecec;border-radius:4px;padding:.25rem .45rem;margin-top:.3rem;max-width:260px;">
        <i class="fas fa-cut"></i>
        <strong>Dipotong {{ \App\Models\GpsTrip::formatDurasi($h['durasi']) }} j</strong>
        · {{ \Carbon\Carbon::parse($h['tanggal'])->format('d/m') }} {{ $h['jam_mulai'] }}–{{ $h['jam_selesai'] }}
        @if (!empty($h['nopol'] ?? $nopol ?? null)) · {{ $h['nopol'] ?? $nopol }} @endif
        <br>
        <span class="text-muted">Berhenti di {{ $h['lokasi'] !== '' ? $h['lokasi'] : 'luar geofence' }}</span>
        @if (!empty($h['peta']))
            · <a href="{{ $h['peta'] }}" target="_blank" rel="noopener">lihat di peta</a>
        @endif
    </div>
@endforeach
