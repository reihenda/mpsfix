@extends('layouts.app')

@section('title', 'Kelompokkan Trip ke Sesi')

@section('page-title', 'Kelompokkan Trip ke Sesi - ' . $operatorGtm->nama)

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <form action="{{ route('operator-gtm.trip-sesi', $operatorGtm->id) }}" method="GET" class="form-inline">
                    <label class="mr-2">Tanggal:</label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <a href="{{ route('operator-gtm.show', $operatorGtm->id) }}" class="btn btn-secondary btn-sm ml-auto">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail Operator
                    </a>
                </form>
            </div>
            <div class="card-body">
                @if ($lembur)
                    <div class="alert alert-info py-2">
                        Data lembur tanggal ini sudah ada (dari input manual atau pengelompokan sebelumnya).
                        Total jam kerja saat ini: <strong>{{ $lembur->total_jam_kerja ?? 0 }} menit</strong>,
                        lembur: <strong>{{ $lembur->total_jam_lembur ?? 0 }} menit</strong>,
                        upah lembur: <strong>Rp {{ number_format($lembur->upah_lembur ?? 0, 0, ',', '.') }}</strong>.
                    </div>
                @endif

                @if (count($kandidatSesi) === 0)
                    <p class="text-muted text-center mb-0">Tidak ada trip yang belum dikelompokkan untuk tanggal ini.</p>
                @else
                    <form action="{{ route('operator-gtm.trip-sesi.store', $operatorGtm->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Kandidat Sesi (auto-suggest berdasarkan perubahan tujuan)</th>
                                    <th style="width: 220px;">Masukkan ke Sesi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kandidatSesi as $index => $kandidat)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $kandidat['asal_nama'] }} → {{ $kandidat['tujuan_nama'] }}</strong>
                                            <ul class="mb-0 small text-muted">
                                                @foreach ($kandidat['items'] as $item)
                                                    <li>{{ $item['label'] }}</li>
                                                @endforeach
                                            </ul>
                                            <input type="hidden" name="items[{{ $index }}]" value="{{ implode(',', $kandidat['refs']) }}">
                                        </td>
                                        <td>
                                            <select name="sesi[{{ $index }}]" class="form-control">
                                                <option value="">-- Tidak dipakai --</option>
                                                <option value="1">Sesi 1</option>
                                                <option value="2">Sesi 2</option>
                                                <option value="3">Sesi 3</option>
                                                <option value="4">Sesi 4</option>
                                                <option value="5">Sesi 5</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p class="text-muted small">Kandidat yang dipilih dengan nomor sesi yang sama akan digabung jadi satu sesi. Jam masuk sesi = waktu paling awal, jam keluar sesi = waktu paling akhir di antara semua trip dalam sesi tersebut.</p>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Pengelompokan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
