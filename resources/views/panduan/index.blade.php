@extends($layout)

@section('title', 'Panduan Sistem')
@section('page-title', 'Panduan Sistem')

@section('content')
@include('panduan._style')

@if (Auth::user()->isOperator())
    <a href="{{ route('operator.dashboard') }}" class="btn btn-sm btn-outline-secondary mb-2">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke dashboard
    </a>
@endif

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('panduan.index') }}" class="panduan-cari">
            <div class="input-group">
                <input type="text" name="q" value="{{ $q }}" class="form-control" autofocus
                       placeholder="Cari panduan, mis. &quot;cara membuat proforma invoice&quot;" aria-label="Cari panduan">
                <div class="input-group-append">
                    <button class="btn btn-info px-3 px-sm-4" type="submit" aria-label="Cari"><i class="fas fa-search"></i><span class="d-none d-sm-inline ml-1">Cari</span></button>
                </div>
            </div>
        </form>
        <small class="text-muted d-block mt-2">
            Menampilkan panduan untuk menu yang bisa kamu akses ({{ $total }} artikel).
        </small>
    </div>
</div>

@if ($q !== '')
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="text-muted">
            {{ $artikel->count() }} hasil untuk <strong>"{{ $q }}"</strong>
        </div>
        <a href="{{ route('panduan.index') }}" class="btn btn-sm btn-outline-secondary">Lihat semua panduan</a>
    </div>

    @forelse ($artikel as $a)
        <a href="{{ route('panduan.show', $a['slug']) }}" class="panduan-kartu">
            <h6>{{ $a['judul'] }}</h6>
            <p>{{ $a['ringkasan'] }}</p>
            @if ($a['menu'])<span class="panduan-menu"><i class="fas fa-bars mr-1"></i>{{ $a['menu'] }}</span>@endif
        </a>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-search fa-2x mb-3 d-block"></i>
                Tidak ada panduan yang cocok. Coba kata lain, misalnya nama menunya
                (mis. "deposit", "invoice", "lembur").
            </div>
        </div>
    @endforelse
@else
    @forelse ($perKategori as $kategori => $list)
        <div class="panduan-kategori">{{ $kategori }}</div>
        <div class="row">
            @foreach ($list as $a)
                <div class="col-md-6">
                    <a href="{{ route('panduan.show', $a['slug']) }}" class="panduan-kartu">
                        <h6>{{ $a['judul'] }}</h6>
                        <p>{{ $a['ringkasan'] }}</p>
                        @if ($a['menu'])<span class="panduan-menu"><i class="fas fa-bars mr-1"></i>{{ $a['menu'] }}</span>@endif
                    </a>
                </div>
            @endforeach
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">Belum ada panduan untuk akun ini.</div></div>
    @endforelse
@endif
@endsection
