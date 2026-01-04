@extends('layouts.app')

@section('title', 'Data Pencatatan Customers')

@section('page-title', 'Daftar Customer')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Customer</h3>
                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                        <div class="card-tools">
                            <a href="{{ route('data-pencatatan.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Data Pencatatan
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="customersTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Customer</th>
                                <th>Email</th>
                                <th>Total Data Pencatatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $customer->dataPencatatan()->count() }} Data
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('data-pencatatan.customer-detail', $customer->id) }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-list"></i> Lihat Data Pencatatan
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daftar FOB Section -->
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar FOB</h3>
                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                        <div class="card-tools">
                            <a href="{{ route('data-pencatatan.fob.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Data FOB
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped" id="fobsTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama FOB</th>
                                <th>Email</th>
                                <th>Total Data Pencatatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($fobs as $fob)
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $fob->name }}</td>
                                    <td>{{ $fob->email }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $fob->dataPencatatan()->count() }} Data
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('data-pencatatan.fob-detail', $fob->id) }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-list"></i> Lihat Data Pencatatan
                                            </a>
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
            $("#customersTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            });

            $("#fobsTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            });
        });
    </script>
@endsection
