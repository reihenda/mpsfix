@extends('layouts.app')

@section('title', 'Kurs USD Bulanan')
@section('page-title', 'Kurs USD Bulanan')

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if (count($bulanKosong))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Kurs belum diisi</strong> untuk bulan yang punya transaksi customer MMBTU:
            @foreach ($bulanKosong as $p)
                <a href="{{ route('kurs-usd.index', ['edit' => $p]) }}" class="badge badge-warning ml-1" onclick="event.preventDefault(); isiPeriode('{{ $p }}');">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $p)->locale('id')->translatedFormat('M Y') }}
                </a>
            @endforeach
            <div class="small mt-1">Sementara itu Rupiah bulan tersebut dihitung memakai kurs bulan terdekat sebelumnya.</div>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dollar-sign mr-2"></i>{{ $edit ? 'Ubah' : 'Input' }} Kurs</h3>
                </div>
                <form method="POST" action="{{ route('kurs-usd.store') }}">
                    @csrf
                    <input type="hidden" name="sumber" id="sumber" value="{{ old('sumber', $edit->sumber ?? 'manual') }}">
                    <input type="hidden" name="sumber_keterangan" id="sumber_keterangan" value="{{ old('sumber_keterangan', $edit->sumber_keterangan ?? '') }}">
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Kurs dipakai untuk menghitung <strong>Rupiah</strong> pemakaian dan saldo customer MMBTU
                            di halaman internal. Pemakaian bulan X memakai kurs bulan X. Customer tidak melihat Rupiah.
                        </p>
                        <div class="form-group">
                            <label for="periode">Bulan <span class="text-danger">*</span></label>
                            <input type="month" class="form-control" id="periode" name="periode"
                                   value="{{ old('periode', $periodeDefault) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="kurs">Kurs 1 USD (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" step="0.01" min="1" class="form-control" id="kurs" name="kurs"
                                       value="{{ old('kurs', $edit->kurs ?? '') }}" placeholder="mis. 16250" required>
                            </div>
                            <div class="d-flex flex-wrap align-items-center mt-2" style="gap: .5rem;">
                                <select class="custom-select custom-select-sm w-auto" id="metode" aria-label="Metode kurs otomatis" title="Cara menghitung kurs bulan dari kurs harian">
                                    <option value="rata">Rata-rata bulan</option>
                                    <option value="akhir">Kurs akhir bulan</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-info" id="btnAmbilKurs">
                                    <i class="fas fa-cloud-download-alt mr-1"></i> <span id="labelAmbilKurs">Ambil kurs</span>
                                </button>
                            </div>
                            <small class="d-block mt-2" id="infoSumber">
                                @php $ketAwal = old('sumber_keterangan', $edit->sumber_keterangan ?? ''); $sumberAwal = old('sumber', $edit->sumber ?? 'manual'); @endphp
                                @if ($sumberAwal !== 'manual' && $ketAwal)
                                    <span class="text-info"><i class="fas fa-info-circle mr-1"></i>Sumber: {{ $ketAwal }}</span>
                                @elseif ($sumberAwal === 'realtime')
                                    <span class="text-info"><i class="fas fa-info-circle mr-1"></i>Sumber: real-time (ExchangeRate-API), diisi sebelum ada keterangan sumber</span>
                                @else
                                    <span class="text-muted">Ketik manual, atau klik "Ambil kurs" untuk mengisi kurs bulan yang dipilih dari kurs referensi ECB.</span>
                                @endif
                            </small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="catatan">Catatan</label>
                            <input type="text" class="form-control" id="catatan" name="catatan" maxlength="255"
                                   value="{{ old('catatan', $edit->catatan ?? '') }}" placeholder="opsional, mis. kurs JISDOR akhir bulan">
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        @if ($edit)
                            <a href="{{ route('kurs-usd.index') }}" class="btn btn-default">Batal</a>
                        @else
                            <span></span>
                        @endif
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Kurs</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Kurs</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">Kurs 1 USD</th>
                                <th>Sumber</th>
                                <th>Catatan</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($daftar as $k)
                                <tr>
                                    <td>{{ $k->periode_label }}</td>
                                    <td class="text-right">Rp {{ number_format($k->kurs, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $k->sumber === 'manual' ? 'secondary' : 'info' }}">{{ $k->sumber_label }}</span>
                                        @if ($k->sumber_keterangan)
                                            <small class="d-block text-muted" style="max-width: 260px;">{{ $k->sumber_keterangan }}</small>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $k->catatan ?: '-' }}</td>
                                    <td class="text-right text-nowrap">
                                        <a href="{{ route('kurs-usd.index', ['edit' => $k->periode]) }}" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('kurs-usd.destroy', $k) }}" class="d-inline"
                                              onsubmit="return confirm('Hapus kurs {{ $k->periode_label }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kurs. Isi kurs bulan berjalan di form sebelah.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
function isiPeriode(p) {
    document.getElementById('periode').value = p;
    document.getElementById('kurs').focus();
}
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnAmbilKurs');
    var label = document.getElementById('labelAmbilKurs');
    var periode = document.getElementById('periode');
    var metode = document.getElementById('metode');
    var kurs = document.getElementById('kurs');
    var sumber = document.getElementById('sumber');
    var ket = document.getElementById('sumber_keterangan');
    var info = document.getElementById('infoSumber');
    var namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    function setInfo(teks, kelas) {
        info.innerHTML = '';
        var span = document.createElement('span');
        span.className = kelas;
        span.textContent = teks;
        info.appendChild(span);
    }
    function perbaruiLabel() {
        var p = (periode.value || '').split('-');
        label.textContent = p.length === 2 ? 'Ambil kurs ' + namaBulan[parseInt(p[1], 10) - 1] + ' ' + p[0] : 'Ambil kurs';
    }
    function jadiManual() {
        sumber.value = 'manual';
        ket.value = '';
        setInfo('Sumber: input manual.', 'text-muted');
    }

    periode.addEventListener('change', function () {
        perbaruiLabel();
        if (sumber.value !== 'manual') { kurs.value = ''; jadiManual(); }
    });
    kurs.addEventListener('input', jadiManual);
    perbaruiLabel();

    btn.addEventListener('click', function () {
        if (!periode.value) { periode.focus(); return; }
        btn.disabled = true;
        setInfo('Mengambil kurs ' + label.textContent.replace('Ambil kurs ', '') + ' dari sumber ECB...', 'text-muted');
        var url = @json(route('kurs-usd.realtime')) + '?periode=' + encodeURIComponent(periode.value) + '&metode=' + encodeURIComponent(metode.value);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    kurs.value = d.kurs;
                    sumber.value = 'api';
                    ket.value = d.keterangan;
                    setInfo('Sumber: ' + d.keterangan + '. Periksa lagi sebelum disimpan.', 'text-info');
                } else {
                    setInfo(d.message || 'Gagal mengambil kurs. Isi kurs secara manual.', 'text-danger');
                }
            })
            .catch(function () { setInfo('Gagal mengambil kurs. Isi kurs secara manual.', 'text-danger'); })
            .finally(function () { btn.disabled = false; });
    });
});
</script>
@endsection
