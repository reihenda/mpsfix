<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">No</th>
                <th>No. Invoice</th>
                <th>Tanggal Invoice</th>
                <th>Periode</th>
                <th>Total Volume</th>
                <th>Total Amount</th>
                <th style="width: 100px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $index => $invoice)
                <tr>
                    <td>{{ $index + $invoices->firstItem() }}</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                    <td>
                        @if($invoice->period_type === 'custom')
                            {{ \Carbon\Carbon::parse($invoice->custom_start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($invoice->custom_end_date)->format('d/m/Y') }}
                        @else
                            {{ \Carbon\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->format('F Y') }}
                        @endif
                    </td>
                    <td>{{ number_format($invoice->total_volume, 2, ',', '.') }} SM3</td>
                    <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data invoice</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
