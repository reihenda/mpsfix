@extends('layouts.app')

@section('title', 'Daftar Billing')

@section('page-title', 'Daftar Billing')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Data Billing</h3>
                <a href="{{ route('billings.select-customer') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle mr-1"></i>Tambah Billing
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

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">No</th>
                            <th>No. Invoice</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Periode</th>
                            <th>Total Biaya</th>
                            <th>Sisa Saldo</th>
                            <th>Harus Dibayar</th>
                            <th style="width: 180px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($billings as $index => $billing)
                            <tr>
                                <td>{{ $index + $billings->firstItem() }}</td>
                                <td>{{ $billing->billing_number }}</td>
                                <td>{{ $billing->customer->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($billing->billing_date)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::createFromDate($billing->period_year, $billing->period_month, 1)->format('F Y') }}
                                </td>
                                <td>Rp {{ number_format($billing->total_amount, 0, ',', '.') }}</td>
                                <td class="{{ $billing->current_balance < 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($billing->current_balance, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($billing->amount_to_pay > 0)
                                        <span class="text-danger">Rp
                                            {{ number_format($billing->amount_to_pay, 0, ',', '.') }}</span>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('billings.show', $billing) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('billings.edit', $billing) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="confirmDelete('{{ route('billings.destroy', $billing) }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data billing</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $billings->links() }}
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
                    <p>Apakah Anda yakin ingin menghapus billing ini?</p>
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

@section('js')
    <script>
        function confirmDelete(url) {
            $('#deleteForm').attr('action', url);
            $('#deleteModal').modal('show');
        }
    </script>
@endsection
