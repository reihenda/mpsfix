@extends('layouts.app')

@section('title', 'Daftar Operator GTM')

@section('page-title', 'Daftar Operator GTM')

@section('css')
<style>
    /* Style untuk kolom update terakhir */
    .table td {
        vertical-align: middle;
    }
    
    .table .text-success {
        font-weight: 600;
    }
    
    .table small {
        font-size: 0.75rem;
        line-height: 1.2;
    }
    
    /* Responsive untuk mobile */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Operator GTM</h3>
                    <div class="card-tools">
                        @if(auth()->user()->role !== 'keuangan')
                        <a href="{{ route('operator-gtm.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Operator
                        </a>
                        @endif
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body table-responsive p-0">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Lokasi Kerja</th>
                                <th>Jam Kerja</th>
                                <th>Update Terakhir</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operators as $key => $operator)
                                <tr>
                                    <td>{{ ($operators->currentPage() - 1) * $operators->perPage() + $key + 1 }}</td>
                                    <td>{{ $operator->nama }}</td>
                                    <td>{{ $operator->lokasi_kerja }}</td>
                                    <td>
                                        <span class="badge badge-{{ $operator->jam_kerja == 8 ? 'primary' : 'info' }}">
                                            {{ $operator->jam_kerja ?? 8 }} Jam
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $lastUpdate = $operator->lemburRecords->first();
                                        @endphp
                                        @if($lastUpdate)
                                            <span class="text-success">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                {{ \Carbon\Carbon::parse($lastUpdate->tanggal)->format('d M Y') }}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                Input: {{ \Carbon\Carbon::parse($lastUpdate->created_at)->diffForHumans() }}
                                            </small>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus-circle mr-1"></i>
                                                Belum ada data
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('operator-gtm.show', $operator->id) }}"
                                            class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        @if(auth()->user()->role !== 'keuangan')
                                        <a href="{{ route('operator-gtm.edit', $operator->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="{{ route('operator-gtm.destroy', $operator->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus operator ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data operator GTM.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                    <div class="float-right">
                        {{ $operators->links() }}
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection
