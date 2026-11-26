@extends('layouts.app')

@section('title', 'Data Pencatatan Customers')

@section('page-title', 'Daftar Customer')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            {{-- Ringkasan peringatan saldo --}}
            @if ($diBawahMinimum->count())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    <strong>{{ $diBawahMinimum->count() }} customer saldonya di bawah minimum:</strong>
                    @foreach ($diBawahMinimum as $c)
                        @php $s = $saldo[$c->id]; @endphp
                        <a href="{{ route('data-pencatatan.customer-detail', $c->id) }}" class="badge badge-light ml-1 text-danger">
                            {{ $c->name }} ({{ $s['mata_uang'] }} {{ number_format($s['saldo'], $s['mata_uang'] === '$' ? 2 : 0, ',', '.') }})
                        </a>
                    @endforeach
                </div>
            @endif
            @if ($belumDiatur->count())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>{{ $belumDiatur->count() }} customer belum diatur minimum saldonya:</strong>
                    {{ $belumDiatur->pluck('name')->implode(', ') }}.
                    @if ($bolehAturMinimum)
                        Klik tombol <strong>Atur</strong> di kolom Minimum Saldo.
                    @endif
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Customer</h3>
                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff())
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
                                <th class="text-right">Sisa Saldo</th>
                                <th class="text-right">Minimum Saldo</th>
                                <th>Status Saldo</th>
                                <th>Total Data Pencatatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($customers as $customer)
                                @php
                                    $s = $saldo[$customer->id];
                                    $desimal = $s['mata_uang'] === '$' ? 2 : 0;
                                    $urutanStatus = ['di_bawah' => 0, 'belum_diatur' => 1, 'aman' => 2, 'tanpa_deposit' => 3, 'tidak_berlaku' => 4][$s['status']];
                                @endphp
                                <tr class="{{ $s['status'] === 'di_bawah' ? 'table-danger' : '' }}">
                                    <td>{{ $no++ }}</td>
                                    <td>
                                        {{ $customer->name }}
                                        @if($customer->isMmbtu())
                                            <span class="badge badge-primary ml-1">MMBTU</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap {{ ($s['saldo'] < 0 || $s['status'] === 'di_bawah') ? 'text-danger font-weight-bold' : '' }}" data-order="{{ $s['saldo'] }}">
                                        {{ $s['saldo'] < 0 ? '-' : '' }}{{ $s['mata_uang'] }} {{ number_format(abs($s['saldo']), $desimal, ',', '.') }}
                                        @if ($s['saldo_mmbtu'] !== null)
                                            <small class="d-block text-muted">{{ number_format($s['saldo_mmbtu'], 2, ',', '.') }} MMBTU</small>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap" data-order="{{ $s['status'] === 'tidak_berlaku' ? -1e17 : ($s['status'] === 'tanpa_deposit' ? -1e15 : ($s['minimum'] ?? -1e16)) }}">
                                        @if ($s['status'] === 'tidak_berlaku')
                                            <span class="text-muted" title="Minimum saldo hanya untuk customer Rupiah">Tidak berlaku</span>
                                        @elseif ($s['status'] === 'tanpa_deposit')
                                            <span class="text-muted">Tidak ada deposit</span>
                                        @elseif ($s['minimum'] === null)
                                            <span class="text-muted">-</span>
                                        @else
                                            {{ $s['mata_uang'] }} {{ number_format($s['minimum'], $desimal, ',', '.') }}
                                        @endif
                                        @if ($bolehAturMinimum && $s['status'] !== 'tidak_berlaku')
                                            <button type="button" class="btn btn-outline-secondary btn-xs ml-1 btn-atur-minimum"
                                                    title="Atur minimum saldo" style="padding: .05rem .4rem; font-size: .75rem;"
                                                    data-url="{{ route('data-pencatatan.saldo-minimum', $customer->id) }}"
                                                    data-nama="{{ $customer->name }}"
                                                    data-minimum="{{ $s['minimum'] ?? '' }}"
                                                    data-tanpa-deposit="{{ $s['status'] === 'tanpa_deposit' ? 1 : 0 }}">
                                                <i class="fas fa-edit"></i> Atur
                                            </button>
                                        @endif
                                    </td>
                                    <td data-order="{{ $urutanStatus }}">
                                        @switch($s['status'])
                                            @case('di_bawah')
                                                <span class="badge badge-danger"><i class="fas fa-exclamation-circle mr-1"></i>Di bawah minimum</span>
                                                <small class="d-block text-danger">kurang {{ $s['mata_uang'] }} {{ number_format(abs($s['selisih']), $desimal, ',', '.') }}</small>
                                                @break
                                            @case('belum_diatur')
                                                <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Belum diatur</span>
                                                @break
                                            @case('tanpa_deposit')
                                                <span class="badge badge-secondary">Tanpa deposit</span>
                                                @break
                                            @case('tidak_berlaku')
                                                <span class="badge badge-light border" title="Minimum saldo hanya untuk customer Rupiah">MMBTU · tidak dipantau</span>
                                                @break
                                            @default
                                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Aman</span>
                                        @endswitch
                                    </td>
                                    <td data-order="{{ $customer->data_pencatatan_count }}">
                                        <span class="badge badge-info">
                                            {{ $customer->data_pencatatan_count }} Data
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

        @if ($bolehAturMinimum)
        {{-- Modal atur minimum saldo --}}
        <div class="modal fade" id="modalMinimumSaldo" tabindex="-1" role="dialog" aria-labelledby="judulMinimumSaldo">
            <div class="modal-dialog" role="document">
                <form method="POST" id="formMinimumSaldo" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="judulMinimumSaldo"><i class="fas fa-sliders-h mr-2"></i>Atur Minimum Saldo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3"><strong id="namaMinimumSaldo"></strong></p>
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="modeNominal" name="mode" value="nominal" class="custom-control-input" checked>
                            <label class="custom-control-label" for="modeNominal">Minimum saldo</label>
                        </div>
                        <div class="input-group mb-3 pl-4" id="grupNominal">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" step="1" min="0" name="saldo_minimum" id="inputMinimumSaldo" class="form-control" placeholder="mis. 10000000">
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="modeTanpaDeposit" name="mode" value="tanpa_deposit" class="custom-control-input">
                            <label class="custom-control-label" for="modeTanpaDeposit">Tidak ada deposit</label>
                        </div>
                        <small class="text-muted d-block pl-4">Untuk customer yang memang tidak memakai deposit sehingga saldonya minus. Tidak akan muncul peringatan.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Daftar FOB Section -->
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar FOB</h3>
                    @if (Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff())
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
                "pageLength": 25,
                // Yang perlu perhatian di atas: di bawah minimum, lalu belum diatur
                "order": [[4, 'asc'], [2, 'asc']],
            });

            // Modal atur minimum saldo
            function aturModeMinimum() {
                var nominal = $('#modeNominal').is(':checked');
                $('#inputMinimumSaldo').prop('disabled', !nominal).prop('required', nominal);
                $('#grupNominal').css('opacity', nominal ? 1 : .5);
            }
            $('input[name="mode"]').on('change', aturModeMinimum);
            $(document).on('click', '.btn-atur-minimum', function () {
                var b = $(this);
                $('#formMinimumSaldo').attr('action', b.data('url'));
                $('#namaMinimumSaldo').text(b.data('nama'));
                $('#inputMinimumSaldo').val(b.data('minimum') !== '' ? Math.round(b.data('minimum')) : '');
                $(b.data('tanpa-deposit') == 1 ? '#modeTanpaDeposit' : '#modeNominal').prop('checked', true);
                aturModeMinimum();
                $('#modalMinimumSaldo').modal('show');
            });

            $("#fobsTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
            });
        });
    </script>
@endsection
