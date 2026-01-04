@extends('layouts.app')

@section('title', 'Detail Rekap Pengambilan')

@section('page-title', 'Detail Rekap Pengambilan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Detail Pengambilan
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('rekap-pengambilan.edit', $rekapPengambilan->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                        <a href="{{ route('rekap-pengambilan.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">ID</th>
                            <td>{{ $rekapPengambilan->id }}</td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td>{{ $rekapPengambilan->customer->name }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal dan Waktu</th>
                            <td>{{ $rekapPengambilan->tanggal->format('d F Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Nomor Polisi</th>
                            <td>{{ $rekapPengambilan->nopol }}</td>
                        </tr>
                        <tr>
                            <th>Volume</th>
                            <td>{{ number_format($rekapPengambilan->volume, 2) }} SM³</td>
                        </tr>
                        <tr>
                            <th>Alamat Pengambilan</th>
                            <td>{{ $rekapPengambilan->alamat_pengambilan ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $rekapPengambilan->keterangan ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Dibuat</th>
                            <td>{{ $rekapPengambilan->created_at->format('d F Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Diperbarui</th>
                            <td>{{ $rekapPengambilan->updated_at->format('d F Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <form action="{{ route('rekap-pengambilan.destroy', $rekapPengambilan->id) }}" method="POST" 
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
