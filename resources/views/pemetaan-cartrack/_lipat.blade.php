{{-- Bagian lipat untuk selisih daftar kendaraan. Tertutup secara bawaan supaya daftar utama tetap pendek. --}}
@if ($daftar->isEmpty())
    <div class="jp-fold is-empty">
        <span>{{ $judul }}</span>
        <span class="jp-badge is-plain is-mute">0</span>
    </div>
@else
    <details class="jp-fold">
        <summary>
            <span><i class="fas fa-chevron-right jp-fold-caret"></i>{{ $judul }}</span>
            <span class="jp-badge is-plain is-warn">{{ $daftar->count() }}</span>
        </summary>
        <div class="jp-fold-body">
            <p class="jp-sub mb-2">{{ $petunjuk }}</p>
            <div class="jp-chips">
                @foreach ($daftar as $n)
                    <span class="jp-chip">{{ $n }}</span>
                @endforeach
            </div>
        </div>
    </details>
@endif
