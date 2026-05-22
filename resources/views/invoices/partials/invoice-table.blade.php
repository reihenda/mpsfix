<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Periode</th>
                <th>Total</th>
                <th>Status</th>
                <th style="width: 110px" class="text-center">Bermaterai</th>
                <th style="width: 120px">Aksi</th>
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
                    <td class="text-center">
                        @if($invoice->materai_file_path)
                            <i class="fas fa-check-circle text-success fa-lg" title="Sudah bermaterai"></i>
                        @else
                            <i class="fas fa-times-circle text-danger fa-lg" title="Belum bermaterai"></i>
                        @endif
                        <div class="btn-group mt-1">
                            <button type="button" class="btn btn-sm btn-primary" title="Upload Bermaterai"
                                    onclick="openUploadModal('{{ route('invoices.upload-materai', $invoice) }}')">
                                <i class="fas fa-upload"></i>
                            </button>
                            <a href="{{ route('invoices.download-materai', $invoice) }}" class="btn btn-sm btn-success" title="Download Bermaterai">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" title="Hapus"
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
