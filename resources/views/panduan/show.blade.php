@extends($layout)

@section('title', $artikel['judul'] . ' — Panduan')
@section('page-title', 'Panduan Sistem')

@section('content')
@include('panduan._style')

<div class="mb-2 d-flex flex-wrap align-items-center justify-content-between">
    <a href="{{ route('panduan.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
        <i class="fas fa-arrow-left mr-1"></i> Semua panduan
    </a>
    <form method="GET" action="{{ route('panduan.index') }}" class="mb-2" style="min-width: 260px;">
        <div class="input-group input-group-sm">
            <input type="text" name="q" class="form-control" placeholder="Cari panduan lain…" aria-label="Cari panduan">
            <div class="input-group-append"><button class="btn btn-info" type="submit"><i class="fas fa-search"></i></button></div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="text-muted small text-uppercase font-weight-bold mb-1">{{ $artikel['kategori'] }}</div>
        <h1 class="panduan-judul mb-2">{{ $artikel['judul'] }}</h1>
        @if ($artikel['menu'])
            <span class="panduan-menu"><i class="fas fa-bars mr-1"></i>Menu: {{ $artikel['menu'] }}</span>
        @endif
        @if ($artikel['ringkasan'])
            <p class="lead mt-3 mb-0" style="font-size: 1.05rem;">{{ $artikel['ringkasan'] }}</p>
        @endif

        @if (count($artikel['alur']))
            <div class="mt-3 small font-weight-bold text-muted">ALUR SINGKAT</div>
            <ol class="panduan-alur">
                @foreach ($artikel['alur'] as $langkah)
                    <li>{{ $langkah }}</li>
                @endforeach
            </ol>
        @endif

        <div class="panduan-isi">
            {!! $artikel['html'] !!}
        </div>
    </div>
</div>

@if ($terkait->count())
    <div class="panduan-kategori">Panduan terkait</div>
    <div class="row">
        @foreach ($terkait as $a)
            <div class="col-md-6">
                <a href="{{ route('panduan.show', $a['slug']) }}" class="panduan-kartu">
                    <h6>{{ $a['judul'] }}</h6>
                    <p>{{ $a['ringkasan'] }}</p>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
