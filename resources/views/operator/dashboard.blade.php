@extends('layouts.operator')

@section('title', 'Dashboard Operator')
@section('page-title', 'Dashboard Operator')

@section('content')
<div class="card">
    <div class="card-body text-center">
        <h5 class="mb-1">Halo, {{ $operatorGtm->nama ?? Auth::user()->name }}</h5>
        <p class="text-muted mb-0">{{ $operatorGtm->lokasi_kerja ?? '-' }}</p>
    </div>
</div>

<a href="{{ route('operator.trip.index') }}" class="card text-decoration-none">
    <div class="card-body d-flex align-items-center">
        <i class="fas fa-route fa-2x text-success mr-3"></i>
        <div>
            <h5 class="mb-0 text-dark">Absen Trip</h5>
            <small class="text-muted">Absen berangkat & sampai untuk perjalanan hari ini</small>
        </div>
    </div>
</a>

<a href="{{ route('operator.perbaikan.index') }}" class="card text-decoration-none">
    <div class="card-body d-flex align-items-center">
        <i class="fas fa-tools fa-2x text-warning mr-3"></i>
        <div>
            <h5 class="mb-0 text-dark">Ajukan Perbaikan Trip</h5>
            <small class="text-muted">Untuk trip yang tidak sempat diabsen karena sistem offline</small>
        </div>
    </div>
</a>
@endsection
