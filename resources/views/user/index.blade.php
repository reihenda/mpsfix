@extends('layouts.app')

@section('title', 'Kelola User')

@section('page-title', 'Kelola User')

@section('content')
    {{-- Tabel Admin --}}
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-user-shield text-primary mr-2"></i>Data Admin
                </h3>
                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#tambahUserModal">
                    <i class="fas fa-user-plus mr-1"></i>Tambah User
                </a>
            </div>
            <div class="mt-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="searchAdmin" class="form-control" placeholder="Cari admin..."
                        value="{{ request('search_admin') }}">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="admin-table-container">
                @include('user.partials.admin-table', ['users' => $adminUsers])
            </div>
        </div>
    </div>

    {{-- Tabel Customer & FOB --}}
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-users text-success mr-2"></i>Data Customer & FOB
                </h3>
                <small class="text-muted">Customer, FOB, dan Demo</small>
            </div>
            <div class="mt-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="searchCustomer" class="form-control" placeholder="Cari customer/FOB..."
                        value="{{ request('search_customer') }}">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="customer-table-container">
                @include('user.partials.customer-table', ['users' => $customerUsers])
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Modal Tambah User --}}
    <div class="modal fade" id="tambahUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus mr-2"></i>
                        Tambah User Baru
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="tambahUserForm" action="{{ route('tambah.user') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="role">Role <span class="text-danger">*</span></label>
                            <select class="form-control @error('role') is-invalid @enderror" id="role"
                                name="role" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="keuangan" {{ old('role') == 'keuangan' ? 'selected' : '' }}>Keuangan</option>
                                <option value="customer" {{ old('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                                <option value="fob" {{ old('role') == 'fob' ? 'selected' : '' }}>FOB</option>
                                <option value="demo" {{ old('role') == 'demo' ? 'selected' : '' }}>Demo</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        {{-- Fields tambahan untuk Customer/FOB/Demo --}}
                        <div id="additional-fields" style="display: none;">
                            <div class="form-group">
                                <label for="no_kontrak">No. Kontrak</label>
                                <input type="text" class="form-control @error('no_kontrak') is-invalid @enderror"
                                    id="no_kontrak" name="no_kontrak" value="{{ old('no_kontrak') }}">
                                @error('no_kontrak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="alamat">Alamat</label>
                                <textarea class="form-control @error('alamat') is-invalid @enderror"
                                    id="alamat" name="alamat" rows="3">{{ old('alamat') }}</textarea>
                                @error('alamat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="nomor_tlpn">Nomor Telepon</label>
                                <input type="text" class="form-control @error('nomor_tlpn') is-invalid @enderror"
                                    id="nomor_tlpn" name="nomor_tlpn" value="{{ old('nomor_tlpn') }}">
                                @error('nomor_tlpn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" required>
                            <small class="text-muted">Minimal 3 karakter</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Tutup
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit User --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit mr-2"></i>
                        Edit User
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editUserForm" action="" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_name">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback">Nama harus diisi</div>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                            <div class="invalid-feedback">Email tidak valid</div>
                        </div>
                        <div class="form-group">
                            <label for="edit_role">Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="keuangan">Keuangan</option>
                                <option value="customer">Customer</option>
                                <option value="fob">FOB</option>
                                <option value="demo">Demo</option>
                            </select>
                            <div class="invalid-feedback">Role harus dipilih</div>
                        </div>
                        
                        {{-- Fields tambahan untuk Customer/FOB/Demo --}}
                        <div id="edit-additional-fields">
                            <div class="form-group">
                                <label for="edit_no_kontrak">No. Kontrak</label>
                                <input type="text" class="form-control" id="edit_no_kontrak" name="no_kontrak">
                            </div>
                            <div class="form-group">
                                <label for="edit_alamat">Alamat</label>
                                <textarea class="form-control" id="edit_alamat" name="alamat" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="edit_nomor_tlpn">Nomor Telepon</label>
                                <input type="text" class="form-control" id="edit_nomor_tlpn" name="nomor_tlpn">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_password">Password <small>(Kosongkan jika tidak ingin mengubah)</small></label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <small class="text-muted">Minimal 3 karakter</small>
                            <div class="invalid-feedback">Password minimal 3 karakter</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Tutup
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus user ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
function confirmDelete(url) {
    $('#deleteForm').attr('action', url);
    $('#deleteModal').modal('show');
}

// Function untuk toggle additional fields berdasarkan role
function toggleAdditionalFields(roleValue, isEdit = false) {
    const additionalFields = isEdit ? $('#edit-additional-fields') : $('#additional-fields');
    
    if (roleValue === 'customer' || roleValue === 'fob' || roleValue === 'demo') {
        additionalFields.show();
    } else {
        additionalFields.hide();
        // Clear values jika disembunyikan
        if (!isEdit) {
            $('#no_kontrak, #alamat, #nomor_tlpn').val('');
        } else {
            $('#edit_no_kontrak, #edit_alamat, #edit_nomor_tlpn').val('');
        }
    }
}

$(document).ready(function() {
    // Timer untuk debounce pencarian
    let searchTimerAdmin, searchTimerCustomer;

    // Event listener untuk pencarian admin
    $('#searchAdmin').on('input', function() {
        const searchTerm = $(this).val();
        clearTimeout(searchTimerAdmin);

        searchTimerAdmin = setTimeout(function() {
            $('#admin-table-container').html(
                '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
            );

            $.ajax({
                url: '{{ route('user.index') }}',
                type: 'GET',
                data: { search_admin: searchTerm },
                dataType: 'json',
                success: function(response) {
                    $('#admin-table-container').html(response.html);
                },
                error: function() {
                    $('#admin-table-container').html(
                        '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>'
                    );
                }
            });
        }, 300);
    });

    // Event listener untuk pencarian customer
    $('#searchCustomer').on('input', function() {
        const searchTerm = $(this).val();
        clearTimeout(searchTimerCustomer);

        searchTimerCustomer = setTimeout(function() {
            $('#customer-table-container').html(
                '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>'
            );

            $.ajax({
                url: '{{ route('user.index') }}',
                type: 'GET',
                data: { search_customer: searchTerm },
                dataType: 'json',
                success: function(response) {
                    $('#customer-table-container').html(response.html);
                },
                error: function() {
                    $('#customer-table-container').html(
                        '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data</div>'
                    );
                }
            });
        }, 300);
    });

    // Toggle additional fields berdasarkan role selection di form tambah
    $('#role').on('change', function() {
        toggleAdditionalFields($(this).val(), false);
    });

    // Toggle additional fields berdasarkan role selection di form edit
    $('#edit_role').on('change', function() {
        toggleAdditionalFields($(this).val(), true);
    });

    // Initial check untuk form tambah jika ada old value
    @if(old('role'))
        toggleAdditionalFields('{{ old('role') }}', false);
    @endif

    // Handler untuk tombol edit user
    $(document).on('click', '.edit-user-btn', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const userEmail = $(this).data('email');
        const userRole = $(this).data('role');
        const userNoKontrak = $(this).data('no_kontrak');
        const userAlamat = $(this).data('alamat');
        const userNomorTlpn = $(this).data('nomor_tlpn');

        // Set action URL untuk form
        $('#editUserForm').attr('action', `{{ route('user.update', '') }}/${userId}`);

        // Isi form dengan data user
        $('#edit_name').val(userName);
        $('#edit_email').val(userEmail);
        $('#edit_role').val(userRole);
        $('#edit_no_kontrak').val(userNoKontrak || '');
        $('#edit_alamat').val(userAlamat || '');
        $('#edit_nomor_tlpn').val(userNomorTlpn || '');
        $('#edit_password').val(''); // Reset password field

        // Toggle additional fields berdasarkan role
        toggleAdditionalFields(userRole, true);

        // Tampilkan modal
        $('#editUserModal').modal('show');
    });

    // Form validation untuk tambah user
    const userForm = $('#tambahUserForm');
    const submitButton = userForm.find('button[type="submit"]');
    const nameInput = userForm.find('input[name="name"]');
    const emailInput = userForm.find('input[name="email"]');
    const passwordInput = userForm.find('input[name="password"]');
    const roleInput = userForm.find('select[name="role"]');

    function validateForm() {
        let isValid = true;

        // Validate name (required)
        if (!nameInput.val().trim()) {
            nameInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            nameInput.removeClass('is-invalid').addClass('is-valid');
        }

        // Validate email (required, valid format)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailInput.val().trim() || !emailRegex.test(emailInput.val())) {
            emailInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            emailInput.removeClass('is-invalid').addClass('is-valid');
        }

        // Validate password (required, min 3 chars)
        if (!passwordInput.val() || passwordInput.val().length < 3) {
            passwordInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            passwordInput.removeClass('is-invalid').addClass('is-valid');
        }

        // Validate role (required)
        if (!roleInput.val()) {
            roleInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            roleInput.removeClass('is-invalid').addClass('is-valid');
        }

        submitButton.prop('disabled', !isValid);
        return isValid;
    }

    // Initial validation
    validateForm();

    // Validate on input
    userForm.find('input, select').on('input change', validateForm);

    // Form submission handler
    userForm.on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: 'Mohon periksa kembali data yang dimasukkan',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });

    // Form validation untuk edit user
    const editUserForm = $('#editUserForm');
    const editSubmitButton = editUserForm.find('button[type="submit"]');
    const editNameInput = editUserForm.find('#edit_name');
    const editEmailInput = editUserForm.find('#edit_email');
    const editPasswordInput = editUserForm.find('#edit_password');

    function validateEditForm() {
        let isValid = true;

        // Validate name (required)
        if (!editNameInput.val().trim()) {
            editNameInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            editNameInput.removeClass('is-invalid').addClass('is-valid');
        }

        // Validate email (required, valid format)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!editEmailInput.val().trim() || !emailRegex.test(editEmailInput.val())) {
            editEmailInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            editEmailInput.removeClass('is-invalid').addClass('is-valid');
        }

        // Validate password (optional, min 3 chars if provided)
        if (editPasswordInput.val() && editPasswordInput.val().length < 3) {
            editPasswordInput.addClass('is-invalid').removeClass('is-valid');
            isValid = false;
        } else {
            editPasswordInput.removeClass('is-invalid');
            if (editPasswordInput.val()) {
                editPasswordInput.addClass('is-valid');
            }
        }

        editSubmitButton.prop('disabled', !isValid);
        return isValid;
    }

    // Initial validation untuk edit form
    validateEditForm();

    // Validate on input untuk edit form
    editUserForm.find('input, select').on('input change', validateEditForm);

    // Form submission handler untuk edit form
    editUserForm.on('submit', function(e) {
        if (!validateEditForm()) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: 'Mohon periksa kembali data yang dimasukkan',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>
@endsection