@extends('layouts.app')

@section('title', 'Kelola Nomor Polisi')

@section('page-title', 'Kelola Nomor Polisi')

@section('content')
    <div class="container-fluid">
        <!-- Notification -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Card for Managing NoPol -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-truck mr-2"></i>Daftar Nomor Polisi
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#tambahNopolModal">
                                <i class="fas fa-plus-circle mr-1"></i> Tambah Nomor Polisi
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="nopolTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">No</th>
                                        <th>Nomor Polisi</th>
                                        <th>Jenis</th>
                                        <th>Ukuran</th>
                                        <th>Area Operasi</th>
                                        <th>No GTM</th>
                                        <th>ISO</th>
                                        <th>Status</th>
                                        <th>COI</th>
                                        <th style="width: 150px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($nomorPolisiList) > 0)
                                        @foreach ($nomorPolisiList as $index => $nopol)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $nopol->nopol }}</td>
                                                <td>{{ $nopol->jenis ?? '-' }}</td>
                                                <td>{{ $nopol->ukuran ? $nopol->ukuran->nama_ukuran : '-' }}</td>
                                                <td>{{ $nopol->area_operasi ?? '-' }}</td>
                                                <td>{{ $nopol->no_gtm ?? '-' }}</td>
                                                <td>{{ $nopol->iso ?? '-' }}</td>
                                                <td>
                                                    @if($nopol->status == 'milik')
                                                        <span class="badge badge-success">Milik</span>
                                                    @elseif($nopol->status == 'sewa')
                                                        <span class="badge badge-info">Sewa</span>
                                                    @elseif($nopol->status == 'disewakan')
                                                        <span class="badge badge-warning">Disewakan</span>
                                                    @elseif($nopol->status == 'FOB')
                                                        <span class="badge badge-dark">FOB</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($nopol->coi == 'sudah')
                                                        <span class="badge badge-success">Sudah</span>
                                                    @elseif($nopol->coi == 'belum')
                                                        <span class="badge badge-danger">Belum</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-sm edit-nopol"
                                                        data-id="{{ $nopol->id }}" 
                                                        data-nopol="{{ $nopol->nopol }}"
                                                        data-keterangan="{{ $nopol->keterangan ?? '' }}"
                                                        data-jenis="{{ $nopol->jenis ?? '' }}"
                                                        data-ukuran-id="{{ $nopol->ukuran_id ?? '' }}"
                                                        data-area-operasi="{{ $nopol->area_operasi ?? '' }}"
                                                        data-no-gtm="{{ $nopol->no_gtm ?? '' }}"
                                                        data-status="{{ $nopol->status ?? '' }}"
                                                        data-iso="{{ $nopol->iso ?? '' }}"
                                                        data-coi="{{ $nopol->coi ?? '' }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('nomor-polisi.destroy', $nopol->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus nomor polisi ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data nomor polisi.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="row mt-3">
            <div class="col-md-12">
                <a href="{{ route('rekap-pengambilan.create') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Rekap Pengambilan
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Nomor Polisi -->
    <div class="modal fade" id="tambahNopolModal" tabindex="-1" role="dialog" aria-labelledby="tambahNopolModalLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false" data-focus="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tambahNopolModalLabel">
                        <i class="fas fa-plus-circle mr-2"></i>Tambah Nomor Polisi
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" tabindex="-1">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('nomor-polisi.store') }}" method="POST" id="formTambahNopol">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">Data Utama</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="nopol">Nomor Polisi <span class="text-danger">*</span></label>
                                            <input type="text" name="nopol" id="nopol" class="form-control form-control-border border-width-2"
                                                placeholder="Contoh: B 1234 ABC" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="jenis">Jenis</label>
                                            <input type="text" name="jenis" id="jenis" class="form-control form-control-border border-width-2"
                                                placeholder="Masukkan jenis">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="status">Status</label>
                                            <select name="status" id="status" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih Status --</option>
                                                <option value="milik">Milik</option>
                                                <option value="sewa">Sewa</option>
                                                <option value="disewakan">Disewakan</option>
                                                <option value="FOB">FOB</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">Spesifikasi</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="iso">ISO</label>
                                            <select name="iso" id="iso" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih ISO --</option>
                                                <option value="ISO - 11439">ISO - 11439</option>
                                                <option value="ISO - 11119">ISO - 11119</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="coi">COI</label>
                                            <select name="coi" id="coi" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih COI --</option>
                                                <option value="sudah">Sudah</option>
                                                <option value="belum">Belum</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">Ukuran & Area</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group ukuran-container">
                                            <label for="ukuran_id">Ukuran</label>
                                            <select name="ukuran_id" id="ukuran_id" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih Ukuran --</option>
                                                @foreach ($ukuranList as $ukuran)
                                                    <option value="{{ $ukuran->id }}">{{ $ukuran->nama_ukuran }}</option>
                                                @endforeach
                                                <option value="tambah_baru">+ Tambah Ukuran Baru</option>
                                            </select>
                                            <div class="ukuran-baru-container mt-2" style="display: none;">
                                                <input type="text" name="ukuran_new" id="ukuran_new" class="form-control form-control-border border-width-2"
                                                    placeholder="Masukkan ukuran baru">
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="area_operasi">Area Operasi</label>
                                            <input type="text" name="area_operasi" id="area_operasi" class="form-control form-control-border border-width-2"
                                                placeholder="Masukkan area operasi">
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-warning mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">No GTM</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-0">
                                            <label for="no_gtm">No GTM</label>
                                            <input type="text" name="no_gtm" id="no_gtm" class="form-control form-control-border border-width-2" placeholder="No GTM" readonly>
                                            <small class="form-text text-muted">No GTM akan terisi otomatis jika status Milik atau Disewakan</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">Keterangan</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-0">
                                            <textarea name="keterangan" id="keterangan" class="form-control form-control-border border-width-2" rows="3"
                                                placeholder="Masukkan keterangan jika ada"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Nomor Polisi -->
    <div class="modal fade" id="editNopolModal" tabindex="-1" role="dialog" aria-labelledby="editNopolModalLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false" data-focus="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editNopolModalLabel">
                        <i class="fas fa-edit mr-2"></i>Edit Nomor Polisi
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close" tabindex="-1">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="" method="POST" id="formEditNopol">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">Data Utama</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="edit_nopol">Nomor Polisi <span class="text-danger">*</span></label>
                                            <input type="text" name="nopol" id="edit_nopol" class="form-control form-control-border border-width-2" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_jenis">Jenis</label>
                                            <input type="text" name="jenis" id="edit_jenis" class="form-control form-control-border border-width-2"
                                                placeholder="Masukkan jenis">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="edit_status">Status</label>
                                            <select name="status" id="edit_status" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih Status --</option>
                                                <option value="milik">Milik</option>
                                                <option value="sewa">Sewa</option>
                                                <option value="disewakan">Disewakan</option>
                                                <option value="FOB">FOB</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">Spesifikasi</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="edit_iso">ISO</label>
                                            <select name="iso" id="edit_iso" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih ISO --</option>
                                                <option value="ISO - 11439">ISO - 11439</option>
                                                <option value="ISO - 11119">ISO - 11119</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="edit_coi">COI</label>
                                            <select name="coi" id="edit_coi" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih COI --</option>
                                                <option value="sudah">Sudah</option>
                                                <option value="belum">Belum</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">Ukuran & Area</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group edit-ukuran-container">
                                            <label for="edit_ukuran_id">Ukuran</label>
                                            <select name="ukuran_id" id="edit_ukuran_id" class="form-control form-control-border border-width-2">
                                                <option value="">-- Pilih Ukuran --</option>
                                                @foreach ($ukuranList as $ukuran)
                                                    <option value="{{ $ukuran->id }}">{{ $ukuran->nama_ukuran }}</option>
                                                @endforeach
                                                <option value="tambah_baru">+ Tambah Ukuran Baru</option>
                                            </select>
                                            <div class="edit-ukuran-baru-container mt-2" style="display: none;">
                                                <input type="text" name="ukuran_new" id="edit_ukuran_new" class="form-control form-control-border border-width-2"
                                                    placeholder="Masukkan ukuran baru">
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="edit_area_operasi">Area Operasi</label>
                                            <input type="text" name="area_operasi" id="edit_area_operasi" class="form-control form-control-border border-width-2"
                                                placeholder="Masukkan area operasi">
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-warning mb-3">
                                    <div class="card-header">
                                        <h3 class="card-title">No GTM</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-0">
                                            <label for="edit_no_gtm">No GTM</label>
                                            <input type="text" name="no_gtm" id="edit_no_gtm" class="form-control form-control-border border-width-2" placeholder="No GTM" readonly>
                                            <small class="form-text text-muted">No GTM akan terisi otomatis jika status Milik atau Disewakan</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">Keterangan</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-0">
                                            <textarea name="keterangan" id="edit_keterangan" class="form-control form-control-border border-width-2" rows="3"
                                                placeholder="Masukkan keterangan jika ada"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Define base URL for JavaScript secara global
        window.BASE_URL = "{{ url('') }}";
        
        // Debugging untuk membantu identifikasi masalah
        $(document).ready(function() {
            console.log('Document ready handler triggered in page script');
            console.log('BASE_URL defined as:', window.BASE_URL);

            // Cek apakah selector menemukan elemen
            var editButtons = $('.edit-nopol');
            console.log('Found ' + editButtons.length + ' edit buttons');
            
            // Debug info atribut data pada tombol edit pertama
            if (editButtons.length > 0) {
                var firstButton = editButtons.first();
                console.log('First edit button data attributes:', {
                    'id': firstButton.attr('data-id'),
                    'nopol': firstButton.attr('data-nopol'),
                    'jenis': firstButton.attr('data-jenis')
                });
            }
        
            // Validasi dilakukan di file nomor-polisi.js
        });
    </script>
    <script src="{{ asset('js/custom/nomor-polisi.js') }}?v={{ time() }}"></script>
@endpush