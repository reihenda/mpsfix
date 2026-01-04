@extends('layouts.app')

@section('title', 'Kelola Deskripsi Transaksi')

@section('page-title', 'Kelola Deskripsi Transaksi')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Deskripsi Transaksi</h3>
            <a href="{{ route('keuangan.descriptions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle mr-1"></i>Tambah Deskripsi
            </a>
        </div>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="mb-3">
            <form action="{{ route('keuangan.descriptions.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2">
                    <select name="category" class="form-control">
                        <option value="">-- Semua Kategori --</option>
                        <option value="kas" {{ request('category') == 'kas' ? 'selected' : '' }}>Kas</option>
                        <option value="bank" {{ request('category') == 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="both" {{ request('category') == 'both' ? 'selected' : '' }}>Keduanya</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                @if(request()->has('category'))
                    <a href="{{ route('keuangan.descriptions.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-times-circle mr-1"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">No</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th style="width: 180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($descriptions as $index => $description)
                        <tr>
                            <td>{{ $index + $descriptions->firstItem() }}</td>
                            <td>{{ $description->description }}</td>
                            <td>
                                @if($description->category == 'kas')
                                    <span class="badge badge-primary">Kas</span>
                                @elseif($description->category == 'bank')
                                    <span class="badge badge-info">Bank</span>
                                @else
                                    <span class="badge badge-success">Keduanya</span>
                                @endif
                            </td>
                            <td>
                                @if($description->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('keuangan.descriptions.edit', $description) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete('{{ route('keuangan.descriptions.destroy', $description) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data deskripsi transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $descriptions->links() }}
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus deskripsi transaksi ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/customer-detail.css') }}">
@endsection

@section('js')
<script>
    function confirmDelete(url) {
        $('#deleteForm').attr('action', url);
        $('#deleteModal').modal('show');
    }
    
    $(document).ready(function() {
        // Add animations to cards on hover
        $('.card').css('opacity', 0); // Initially hide
        
        // Animate elements one by one when page loads
        $(window).on('load', function() {
            $('.card').each(function(i) {
                $(this).delay(i * 150).animate({
                    'opacity': 1
                }, 500);
            });
        });
    });
</script>
@endsection
