<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="bg-primary text-white">
            <tr>
                <th>No</th>
                <th>Nomor Proforma</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Periode</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Berlaku Sampai</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proformaInvoices as $index => $proforma)
                <tr>
                    <td>{{ $proformaInvoices->firstItem() + $index }}</td>
                    <td>
                        <strong>{{ $proforma->proforma_number }}</strong>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="customer-avatar mr-2">
                                {{ substr($proforma->customer->name, 0, 1) }}
                            </div>
                            {{ $proforma->customer->name }}
                        </div>
                    </td>
                    <td>{{ $proforma->proforma_date->format('d/m/Y') }}</td>
                    <td>
                        <small class="text-muted">
                            {{ $proforma->period_formatted }}
                        </small>
                    </td>
                    <td>
                        <strong class="text-success">
                            Rp {{ number_format($proforma->total_amount, 0, ',', '.') }}
                        </strong>
                        <br>
                        <small class="text-muted">
                            {{ number_format($proforma->total_volume, 2) }} m³
                        </small>
                    </td>
                    <td>
                        @php
                            $statusClass = [
                                'draft' => 'badge-secondary',
                                'sent' => 'badge-info',
                                'expired' => 'badge-danger',
                                'converted' => 'badge-success'
                            ];
                            $statusText = [
                                'draft' => 'Draft',
                                'sent' => 'Terkirim',
                                'expired' => 'Kadaluarsa',
                                'converted' => 'Dikonversi'
                            ];
                        @endphp
                        <span class="badge {{ $statusClass[$proforma->status] ?? 'badge-secondary' }}">
                            {{ $statusText[$proforma->status] ?? ucfirst($proforma->status) }}
                        </span>
                        
                        @if($proforma->validity_date && $proforma->days_until_expiry !== null)
                            <br>
                            <small class="text-muted">
                                @if($proforma->days_until_expiry > 0)
                                    {{ $proforma->days_until_expiry }} hari lagi
                                @elseif($proforma->days_until_expiry == 0)
                                    <span class="text-warning">Hari ini</span>
                                @else
                                    <span class="text-danger">Kadaluarsa</span>
                                @endif
                            </small>
                        @endif
                    </td>
                    <td>
                        @if($proforma->validity_date)
                            {{ $proforma->validity_date->format('d/m/Y') }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('proforma-invoices.show', $proforma) }}" 
                               class="btn btn-sm btn-info" 
                               title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('proforma-invoices.edit', $proforma) }}" 
                               class="btn btn-sm btn-warning" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="confirmDelete('{{ route('proforma-invoices.destroy', $proforma) }}')"
                                    title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                        <br>
                        Belum ada proforma invoice
                        <br>
                        <a href="{{ route('proforma-invoices.select-customer') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus mr-1"></i>Tambah Proforma Invoice
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
.customer-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 12px;
}
</style>
