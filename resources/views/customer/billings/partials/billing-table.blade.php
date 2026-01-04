<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">No</th>
                <th>No. Billing</th>
                <th>Tanggal Billing</th>
                <th>Periode</th>
                <th>Total Volume</th>
                <th>Total Pemakaian</th>
                <th>Total Deposit</th>
                <th>Saldo Akhir</th>
                <th>Yang Harus Dibayar</th>
                <th style="width: 100px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($billings as $index => $billing)
                <tr>
                    <td>{{ $index + $billings->firstItem() }}</td>
                    <td>{{ $billing->billing_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($billing->billing_date)->format('d/m/Y') }}</td>
                    <td>
                        @if($billing->period_type === 'custom')
                            {{ \Carbon\Carbon::parse($billing->custom_start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($billing->custom_end_date)->format('d/m/Y') }}
                        @else
                            {{ \Carbon\Carbon::createFromDate($billing->period_year, $billing->period_month, 1)->format('F Y') }}
                        @endif
                    </td>
                    <td>{{ number_format($billing->total_volume, 2, ',', '.') }} SM3</td>
                    <td>Rp {{ number_format($billing->total_amount, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($billing->total_deposit, 0, ',', '.') }}</td>
                    <td>
                        @if($billing->current_balance < 0)
                            <span class="text-danger">Rp {{ number_format(abs($billing->current_balance), 0, ',', '.') }}</span>
                        @else
                            <span class="text-success">Rp {{ number_format($billing->current_balance, 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($billing->amount_to_pay > 0)
                            <span class="text-danger font-weight-bold">Rp {{ number_format($billing->amount_to_pay, 0, ',', '.') }}</span>
                        @else
                            <span class="text-success">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('billings.show', $billing) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Belum ada data billing</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
