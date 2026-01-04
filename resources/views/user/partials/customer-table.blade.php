<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>No. Kontrak</th>
                <th>Alamat</th>
                <th>No. Telepon</th>
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
                        <td colspan="9" class="font-weight-bold">
                            @if($user->role == 'customer')
                                <i class="fas fa-user-tie text-success mr-2"></i> Customer
                            @elseif($user->role == 'fob')
                                <i class="fas fa-truck text-warning mr-2"></i> FOB
                            @else
                                <i class="fas fa-user-graduate text-info mr-2"></i> Demo
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->no_kontrak ?? '-' }}</td>
                    <td>{{ $user->alamat ? Str::limit($user->alamat, 30) : '-' }}</td>
                    <td>{{ $user->nomor_tlpn ?? '-' }}</td>
                    <td>
                        <span class="badge 
                            @if($user->role == 'customer') badge-success
                            @elseif($user->role == 'fob') badge-warning
                            @else badge-info
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
                            @if($user->isCustomer())
                                <a href="{{ route('data-pencatatan.customer-detail', $user->id) }}"
                                    class="btn btn-info btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @elseif($user->isFOB())
                                <a href="{{ route('data-pencatatan.fob-detail', $user->id) }}"
                                    class="btn btn-info btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                            <button class="btn btn-warning btn-sm edit-user-btn" title="Edit User"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" 
                                data-email="{{ $user->email }}" data-role="{{ $user->role }}"
                                data-no_kontrak="{{ $user->no_kontrak }}" data-alamat="{{ $user->alamat }}"
                                data-nomor_tlpn="{{ $user->nomor_tlpn }}">
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
                    <td colspan="9" class="text-center">Tidak ada data customer/FOB yang ditemukan</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>