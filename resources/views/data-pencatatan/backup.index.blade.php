@extends('layouts.app')

@section('title', 'Daftar Data Pencatatan')

@section('page-title', 'Daftar Data Pencatatan')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Pencatatan</h3>
                    <div class="card-tools">
                        <a href="{{ route('data-pencatatan.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tambah Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="data-pencatatan-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Customer</th>
                                <th>Tanggal Input</th>
                                <th>Detail Data</th>
                                <th>Harga Final</th>
                                <th>Status Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataPencatatan as $index => $data)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $data->nama_customer }}</td>
                                    <td>{{ $data->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        @php
                                            $inputData = json_decode($data->data_input, true);
                                        @endphp
                                        @foreach ($inputData as $key => $value)
                                            {{ ucfirst($key) }}: {{ $value }}<br>
                                        @endforeach
                                    </td>
                                    <td>Rp {{ number_format($data->harga_final, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($data->status_pembayaran == 'belum_lunas')
                                            <span class="badge bg-danger">Belum Lunas</span>
                                        @else
                                            <span class="badge bg-success">Lunas</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('data-pencatatan.show', $data->id) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('data-pencatatan.edit', $data->id) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('data-pencatatan.destroy', $data->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Yakin ingin menghapus?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function() {
            $("#data-pencatatan-table").DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#data-pencatatan-table_wrapper .col-md-6:eq(0)');
        });
    </script>
@endsection
