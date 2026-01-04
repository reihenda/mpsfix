@extends('layouts.app')

@section('title', 'Tambah Data Lembur')

@section('page-title', 'Tambah Data Lembur Operator')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Form Tambah Data Lembur</h3>
            </div>
            <!-- /.card-header -->
            
            <!-- form start -->
            <form action="{{ route('operator-gtm.store-lembur', $operatorGtm->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Operator:</label>
                        <p><strong>{{ $operatorGtm->nama }}</strong> ({{ $operatorGtm->lokasi_kerja }})</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Sesi 1</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jam_masuk_sesi_1">Jam Masuk</label>
                                        <input type="time" class="form-control @error('jam_masuk_sesi_1') is-invalid @enderror" id="jam_masuk_sesi_1" name="jam_masuk_sesi_1" value="{{ old('jam_masuk_sesi_1') }}">
                                        @error('jam_masuk_sesi_1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jam_keluar_sesi_1">Jam Keluar</label>
                                        <input type="time" class="form-control @error('jam_keluar_sesi_1') is-invalid @enderror" id="jam_keluar_sesi_1" name="jam_keluar_sesi_1" value="{{ old('jam_keluar_sesi_1') }}">
                                        @error('jam_keluar_sesi_1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Sesi 2</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jam_masuk_sesi_2">Jam Masuk</label>
                                        <input type="time" class="form-control @error('jam_masuk_sesi_2') is-invalid @enderror" id="jam_masuk_sesi_2" name="jam_masuk_sesi_2" value="{{ old('jam_masuk_sesi_2') }}">
                                        @error('jam_masuk_sesi_2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jam_keluar_sesi_2">Jam Keluar</label>
                                        <input type="time" class="form-control @error('jam_keluar_sesi_2') is-invalid @enderror" id="jam_keluar_sesi_2" name="jam_keluar_sesi_2" value="{{ old('jam_keluar_sesi_2') }}">
                                        @error('jam_keluar_sesi_2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dynamic Sessions Container -->
                    <div id="dynamic-sessions-container">
                        <!-- Sesi tambahan akan ditambahkan di sini -->
                    </div>
                    
                    <!-- Button Tambah Sesi -->
                    <div class="text-center mb-3">
                        <button type="button" id="btn-tambah-sesi" class="btn btn-success">
                            <i class="fas fa-plus mr-1"></i> Tambah Sesi
                        </button>
                        <div id="max-session-message" class="alert alert-warning mt-2" style="display: none;">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Maksimal 5 sesi dapat ditambahkan.
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Sistem akan menghitung total jam kerja, jam lembur, dan upah lembur secara otomatis berdasarkan data yang dimasukkan.
                        <br>
                        <small>Jam lembur dihitung jika total jam kerja melebihi <strong>{{ $operatorGtm->jam_kerja ?? 8 }} jam</strong> (sesuai setting jam kerja operator ini).</small>
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                    <a href="{{ route('operator-gtm.show', $operatorGtm->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
        <!-- /.card -->
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let currentSessionCount = 2; // Mulai dengan 2 sesi default
    const maxSessions = 5;
    
    // Function untuk membuat HTML sesi baru
    function createSessionHtml(sessionNumber) {
        return `
            <div class="card card-success session-card" data-session="${sessionNumber}">
                <div class="card-header">
                    <h3 class="card-title">Sesi ${sessionNumber}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool btn-remove-session" data-session="${sessionNumber}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jam_masuk_sesi_${sessionNumber}">Jam Masuk</label>
                                <input type="time" class="form-control" id="jam_masuk_sesi_${sessionNumber}" name="jam_masuk_sesi_${sessionNumber}" value="{{ old('jam_masuk_sesi_${sessionNumber}') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jam_keluar_sesi_${sessionNumber}">Jam Keluar</label>
                                <input type="time" class="form-control" id="jam_keluar_sesi_${sessionNumber}" name="jam_keluar_sesi_${sessionNumber}" value="{{ old('jam_keluar_sesi_${sessionNumber}') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Function untuk update status button
    function updateButtonStatus() {
        if (currentSessionCount >= maxSessions) {
            $('#btn-tambah-sesi').hide();
            $('#max-session-message').show();
        } else {
            $('#btn-tambah-sesi').show();
            $('#max-session-message').hide();
        }
    }
    
    // Event handler untuk tambah sesi
    $('#btn-tambah-sesi').click(function() {
        if (currentSessionCount < maxSessions) {
            currentSessionCount++;
            const sessionHtml = createSessionHtml(currentSessionCount);
            $('#dynamic-sessions-container').append(sessionHtml);
            updateButtonStatus();
        }
    });
    
    // Event handler untuk hapus sesi (menggunakan event delegation)
    $(document).on('click', '.btn-remove-session', function() {
        const sessionToRemove = $(this).data('session');
        $(`.session-card[data-session="${sessionToRemove}"]`).remove();
        
        // Reorder session numbers
        let newSessionCount = 2;
        $('.session-card').each(function() {
            newSessionCount++;
            const newSessionNumber = newSessionCount;
            const oldSessionNumber = $(this).data('session');
            
            // Update data attribute
            $(this).attr('data-session', newSessionNumber);
            
            // Update title
            $(this).find('.card-title').text(`Sesi ${newSessionNumber}`);
            
            // Update form elements
            $(this).find('input, label').each(function() {
                const element = $(this);
                if (element.is('input')) {
                    const name = element.attr('name');
                    const id = element.attr('id');
                    if (name) {
                        element.attr('name', name.replace(`sesi_${oldSessionNumber}`, `sesi_${newSessionNumber}`));
                    }
                    if (id) {
                        element.attr('id', id.replace(`sesi_${oldSessionNumber}`, `sesi_${newSessionNumber}`));
                    }
                } else if (element.is('label')) {
                    const forAttr = element.attr('for');
                    if (forAttr) {
                        element.attr('for', forAttr.replace(`sesi_${oldSessionNumber}`, `sesi_${newSessionNumber}`));
                    }
                }
            });
            
            // Update remove button data
            $(this).find('.btn-remove-session').attr('data-session', newSessionNumber);
        });
        
        currentSessionCount = newSessionCount;
        updateButtonStatus();
    });
    
    // Initial status update
    updateButtonStatus();
});
</script>
@endsection