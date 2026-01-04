<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>NOPOL</th>
                <th>Volume (SM³)</th>
                <th>Alamat Pengambilan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @if (count($rekapPengambilan) > 0)
                @php $no = 1; @endphp
                @foreach ($rekapPengambilan as $rekap)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $rekap->customer->name }}</td>
                        <td>{{ $rekap->tanggal->format('d M Y H:i') }}</td>
                        <td>{{ $rekap->nopol }}</td>
                        <td class="volume-cell" data-volume="{{ $rekap->volume }}">{{ number_format($rekap->volume, 2) }}</td>
                        <td>{{ $rekap->alamat_pengambilan ?: '-' }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('rekap-pengambilan.show', $rekap->id) }}"
                                    class="btn btn-info btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('rekap-pengambilan.edit', $rekap->id) }}"
                                    class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Hapus"
                                        onclick="confirmDelete('{{ route('rekap-pengambilan.destroy', $rekap->id) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data rekap pengambilan yang ditemukan</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bg-light font-weight-bold">
                <td colspan="4" class="text-right">Total Volume:</td>
                <td id="total-volume-display">0.00</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
// Function untuk konfirmasi delete
function confirmDelete(url) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        // Create form dynamically
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        // Add CSRF token
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Add method override
        var methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
}
</script>