@extends('layouts.app')

@section('title', 'Detail Data Pencatatan')

@section('page-title', 'Detail Data Pencatatan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Informasi Detail Data Pencatatan</h3>
                </div>
                <div class="card-body">
                    {{-- Decode data input --}}
                    @php
                        $dataInput = json_decode($dataPencatatan->data_input, true);
                    @endphp

                    {{-- Pembacaan Awal Section --}}
                    <div class="card card-secondary mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Pembacaan Awal</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Waktu Pembacaan Awal:</strong>
                                    <p>{{ \Carbon\Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d M Y H:i:s') }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Volume Pembacaan Awal:</strong>
                                    <p>{{ number_format($dataInput['pembacaan_awal']['volume'], 3) }} m³</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pembacaan Akhir Section --}}
                    <div class="card card-secondary mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Pembacaan Akhir</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Waktu Pembacaan Akhir:</strong>
                                    <p>{{ \Carbon\Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d M Y H:i:s') }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Volume Pembacaan Akhir:</strong>
                                    <p>{{ number_format($dataInput['pembacaan_akhir']['volume'], 3) }} m³</p>
                                </div>
                            </div>
                        </div>
                    </div>



                    {{-- Pembacaan Flow Meter Section --}}
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Pembacaan Flow Meter</h3>
                        </div>
                        <div class="card-body">
                            <strong>Volume Flow Meter:</strong>
                            <p>{{ number_format($dataInput['volume_flow_meter'], 3) }} m³</p>
                        </div>
                    </div>

                    {{-- Customer and Payment Information --}}
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Nama Customer:</strong>
                            <p>{{ $dataPencatatan->nama_customer }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Status Pembayaran:</strong>
                            <p>
                                @switch($dataPencatatan->status_pembayaran)
                                    @case('lunas')
                                        <span class="badge badge-success">Lunas</span>
                                    @break

                                    @case('belum_lunas')
                                        <span class="badge badge-warning">Belum Lunas</span>
                                    @break

                                    @default
                                        <span class="badge badge-secondary">Tidak Diketahui</span>
                                @endswitch
                            </p>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="row mt-3">
                        <div class="col-md-12">
                            @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                <a href="{{ route('data-pencatatan.edit', $dataPencatatan->id) }}"
                                    class="btn btn-primary mr-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('data-pencatatan.destroy', $dataPencatatan->id) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            @elseif(Auth::user()->isCustomer() && $dataPencatatan->status_pembayaran !== 'lunas')
                                <form action="{{ route('data-pencatatan.proses-pembayaran', $dataPencatatan->id) }}"
                                    method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-money-bill-wave"></i> Proses Pembayaran
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('data-pencatatan.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
