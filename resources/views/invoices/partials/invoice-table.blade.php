<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">No</th>
                <th>No. Invoice</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Periode</th>
                <th>Total</th>
                <th>Status</th>
                <th style="width: 180px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $index => $invoice)
                <tr>
                    <td>{{ $index + $invoices->firstItem() }}</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->format('F Y') }}</td>
                    <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    <td>
                        @if($invoice->status == 'paid')
                            <span class="badge badge-success">Lunas</span>
                        @elseif($invoice->status == 'partial')
                            <span class="badge badge-warning">Sebagian</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="badge badge-danger">Dibatalkan</span>
                        @else
                            <span class="badge badge-secondary">Belum Lunas</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="confirmDelete('{{ route('invoices.destroy', $invoice) }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada data invoice</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>