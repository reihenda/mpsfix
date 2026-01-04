@extends('layouts.app')

@section('title', 'Analisis Data FOB')

@section('page-title', 'Debug - Analisis Data FOB: ' . $customer->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Analisis Data FOB: {{ $customer->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('data-pencatatan.fob-detail', $customer->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail FOB
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Card -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">📊 Ringkasan Data</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total Records Pencatatan</td>
                                            <td><strong>{{ $analysis['summary']['total_pencatatan_records'] }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Records Rekap</td>
                                            <td><strong>{{ $analysis['summary']['total_rekap_records'] }}</strong></td>
                                        </tr>
                                        <tr class="{{ $analysis['summary']['duplicates_found'] > 0 ? 'table-warning' : '' }}">
                                            <td>Duplikat Ditemukan</td>
                                            <td>
                                                <strong>{{ $analysis['summary']['duplicates_found'] }}</strong>
                                                @if($analysis['summary']['duplicates_found'] > 0)
                                                    <span class="badge badge-warning ml-2">⚠️ Perlu Dibersihkan</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="{{ $analysis['summary']['records_without_harga'] > 0 ? 'table-warning' : '' }}">
                                            <td>Records Tanpa Harga</td>
                                            <td>
                                                <strong>{{ $analysis['summary']['records_without_harga'] }}</strong>
                                                @if($analysis['summary']['records_without_harga'] > 0)
                                                    <span class="badge badge-warning ml-2">⚠️ Perlu Diperbaiki</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="{{ $analysis['summary']['missing_from_pencatatan'] > 0 ? 'table-danger' : '' }}">
                                            <td>Data Hilang dari Pencatatan</td>
                                            <td>
                                                <strong>{{ $analysis['summary']['missing_from_pencatatan'] }}</strong>
                                                @if($analysis['summary']['missing_from_pencatatan'] > 0)
                                                    <span class="badge badge-danger ml-2">❌ Perlu Sinkronisasi</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="{{ $analysis['summary']['extra_in_pencatatan'] > 0 ? 'table-info' : '' }}">
                                            <td>Data Extra di Pencatatan</td>
                                            <td>
                                                <strong>{{ $analysis['summary']['extra_in_pencatatan'] }}</strong>
                                                @if($analysis['summary']['extra_in_pencatatan'] > 0)
                                                    <span class="badge badge-info ml-2">ℹ️ Perlu Diperiksa</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-{{ $analysis['totals']['is_consistent'] ? 'success' : 'danger' }}">
                                <div class="card-header">
                                    <h3 class="card-title">💰 Konsistensi Total</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total Manual (dari harga_final)</td>
                                            <td><strong>Rp {{ number_format($analysis['totals']['manual_total'], 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Tersimpan (total_purchases)</td>
                                            <td><strong>Rp {{ number_format($analysis['totals']['stored_total'], 2) }}</strong></td>
                                        </tr>
                                        <tr class="{{ $analysis['totals']['is_consistent'] ? 'table-success' : 'table-danger' }}">
                                            <td>Selisih</td>
                                            <td>
                                                <strong>Rp {{ number_format($analysis['totals']['difference'], 2) }}</strong>
                                                @if($analysis['totals']['is_consistent'])
                                                    <span class="badge badge-success ml-2">✅ Konsisten</span>
                                                @else
                                                    <span class="badge badge-danger ml-2">❌ Tidak Konsisten</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">🔧 Tindakan Perbaikan</h3>
                                </div>
                                <div class="card-body">
                                    <div class="btn-group" role="group">
                                        @if($analysis['summary']['duplicates_found'] > 0)
                                            <form method="POST" action="{{ route('debug.fob.clean-duplicates', $customer->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus {{ $analysis['summary']['duplicates_found'] }} data duplikat?')">
                                                @csrf
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-broom mr-1"></i>
                                                    Bersihkan {{ $analysis['summary']['duplicates_found'] }} Duplikat
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if(!$analysis['totals']['is_consistent'])
                                            <form method="POST" action="{{ route('debug.fob.validate-consistency', $customer->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin memperbaiki inkonsistensi total sebesar Rp {{ number_format($analysis['totals']['difference'], 2) }}?')">
                                                @csrf
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-balance-scale mr-1"></i>
                                                    Perbaiki Inkonsistensi
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <a href="{{ route('data-pencatatan.fob.sync-data', $customer->id) }}" class="btn btn-info" onclick="return confirm('Yakin ingin melakukan sinkronisasi penuh?')">
                                            <i class="fas fa-sync mr-1"></i>
                                            Sinkronisasi Penuh
                                        </a>
                                        
                                        <button type="button" class="btn btn-success" onclick="location.reload()">
                                            <i class="fas fa-redo mr-1"></i>
                                            Refresh Analisis
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detail Tables -->
                    @if(count($analysis['duplicates']) > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">🔍 Data Duplikat Ditemukan</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID Original</th>
                                                    <th>ID Duplikat</th>
                                                    <th>Tanggal</th>
                                                    <th>Volume</th>
                                                    <th>Harga Original</th>
                                                    <th>Harga Duplikat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($analysis['duplicates'] as $duplicate)
                                                <tr>
                                                    <td>{{ $duplicate['original_id'] }}</td>
                                                    <td>{{ $duplicate['duplicate_id'] }}</td>
                                                    <td>{{ $duplicate['date'] }}</td>
                                                    <td>{{ $duplicate['volume'] }}</td>
                                                    <td>Rp {{ number_format($duplicate['original_harga'], 2) }}</td>
                                                    <td>Rp {{ number_format($duplicate['duplicate_harga'], 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if(count($analysis['records_without_harga']) > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">💸 Records Tanpa Harga Final</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Tanggal</th>
                                                    <th>Volume</th>
                                                    <th>Harga Final</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($analysis['records_without_harga'] as $record)
                                                <tr>
                                                    <td>{{ $record['id'] }}</td>
                                                    <td>{{ $record['date'] }}</td>
                                                    <td>{{ $record['volume'] }}</td>
                                                    <td>Rp {{ number_format($record['harga_final'], 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    @if(count($analysis['missing_dates']) > 0)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-danger">
                                <div class="card-header">
                                    <h3 class="card-title">❌ Tanggal Hilang dari Pencatatan</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($analysis['missing_dates'] as $date)
                                                <tr>
                                                    <td>{{ $date }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(count($analysis['extra_dates']) > 0)
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">ℹ️ Tanggal Extra di Pencatatan</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($analysis['extra_dates'] as $date)
                                                <tr>
                                                    <td>{{ $date }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Auto refresh setiap 30 detik jika ada masalah
    @if(!$analysis['totals']['is_consistent'] || $analysis['summary']['duplicates_found'] > 0)
    setInterval(function() {
        if(confirm('Data mungkin sudah berubah. Refresh analisis?')) {
            location.reload();
        }
    }, 30000);
    @endif
});
</script>
@endsection
