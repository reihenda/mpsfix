<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th style="width: 10px">No</th>
                <th>Nama Customer</th>
                <th>ID Pelanggan</th>
                <th>Role</th>
                <th>Jumlah Invoice</th>
                <th style="width: 100px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $index => $customer)
                <tr>
                    <td>{{ $index + $customers->firstItem() }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="mr-2" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:14px;flex-shrink:0;">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                            {{ $customer->name }}
                        </div>
                    </td>
                    <td>{{ $customer->id_pelanggan ?? '-' }}</td>
                    <td>
                        @if($customer->role === 'fob')
                            <span class="badge badge-warning">FOB</span>
                        @elseif($customer->role === 'mmbtu')
                            <span class="badge badge-info">MMBTU</span>
                        @else
                            <span class="badge badge-secondary">Customer</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-primary">{{ $customer->invoices_count }} invoice</span>
                    </td>
                    <td>
                        <a href="{{ route('invoices.customer-list', $customer) }}" class="btn btn-sm btn-info" title="Lihat Invoice">
                            <i class="fas fa-file-invoice"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data customer</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
