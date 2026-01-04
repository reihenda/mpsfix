<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tanggal Dibuat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $currentRole = null;
            @endphp
            @foreach ($users as $user)
                @if ($currentRole !== $user->role)
                    @php $currentRole = $user->role; @endphp
                    <tr class="bg-light">
                        <td colspan="6" class="font-weight-bold">
                            @if($user->role == 'superadmin')
                                <i class="fas fa-crown text-danger mr-2"></i> Superadmin
                            @elseif($user->role == 'admin')
                                <i class="fas fa-user-shield text-primary mr-2"></i> Admin
                            @elseif($user->role == 'keuangan')
                                <i class="fas fa-calculator text-success mr-2"></i> Keuangan
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge 
                            @if($user->role == 'superadmin') badge-danger
                            @elseif($user->role == 'admin') badge-primary
                            @elseif($user->role == 'keuangan') badge-success
                            @endif">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge badge-success">Aktif</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm edit-user-btn" title="Edit User"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" 
                                data-email="{{ $user->email }}" data-role="{{ $user->role }}"
                                data-no_kontrak="" data-alamat=""
                                data-nomor_tlpn="">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="confirmDelete('{{ route('user.destroy', $user->id) }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
            @if(count($users) == 0)
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data admin yang ditemukan</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>