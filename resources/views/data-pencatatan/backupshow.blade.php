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
                    {{-- Customer Information --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Nama Customer:</strong>
                            <p>{{ $dataPencatatan->nama_customer }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Tanggal Input:</strong>
                            <p>{{ $dataPencatatan->created_at->format('d M Y H:i:s') }}</p>
                        </div>
                    </div>

                    {{-- Parsed Data Input --}}
                    @php
                        $dataInput = json_decode($dataPencatatan->data_input, true);
                    @endphp

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h5>Detail Input</h5>
                            <table class="table table-bordered">
                                <tbody>
                                    @if (is_array($dataInput))
                                        @foreach ($dataInput as $key => $value)
                                            <tr>
                                                <th class="text-capitalize">{{ str_replace('_', ' ', $key) }}</th>
                                                <td>
                                                    @if ($key === 'volume')
                                                        {{ $value }} m³
                                                    @elseif($key === 'kompleksitas')
                                                        {{ $value }} (Skala 1-10)
                                                    @else
                                                        {{ $value ?: 'Tidak ada' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="2" class="text-center">Data input tidak valid</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Financial Information --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Total Harga:</strong>
                            <p>Rp {{ number_format($dataPencatatan->total_harga, 0, ',', '.') }}</p>
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
                    <div class="row">
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

@section('js')
    <script>
        $(function() {
            // Optional: Add any additional JavaScript for the detail page
        });
    </script>
@endsection
