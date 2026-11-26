<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Notifikasi Saldo</title></head>
<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2d3d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
    <tr>
        <td style="background:#3f51b5;color:#ffffff;padding:16px 24px;font-size:18px;font-weight:bold;">
            Sistem Pencatatan MPS — Notifikasi Saldo
        </td>
    </tr>
    <tr>
        <td style="padding:24px;">
            @if ($tes)
                <p style="margin:0 0 16px;padding:8px 12px;background:#fff8e1;border-left:4px solid #ffc107;">
                    Ini email <strong>tes</strong>. Notifikasi rutin dikirim setiap hari pukul 09.00 WIB hanya bila ada customer di bawah minimum.
                </p>
            @endif

            <p style="margin:0 0 16px;">Kondisi saldo customer per <strong>{{ $tanggal }}</strong>:</p>

            @if ($diBawah->count())
                <p style="margin:0 0 8px;color:#c0392b;font-weight:bold;">
                    {{ $diBawah->count() }} customer saldonya di bawah minimum:
                </p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                    <tr style="background:#fdecea;">
                        <th align="left" style="padding:8px;border:1px solid #e0e0e0;">Customer</th>
                        <th align="right" style="padding:8px;border:1px solid #e0e0e0;">Sisa Saldo</th>
                        <th align="right" style="padding:8px;border:1px solid #e0e0e0;">Minimum</th>
                        <th align="right" style="padding:8px;border:1px solid #e0e0e0;">Kurang</th>
                    </tr>
                    @foreach ($diBawah as $c)
                        <tr>
                            <td style="padding:8px;border:1px solid #e0e0e0;"><a href="{{ $c['url'] }}" style="color:#3f51b5;">{{ $c['nama'] }}</a></td>
                            <td align="right" style="padding:8px;border:1px solid #e0e0e0;color:#c0392b;font-weight:bold;white-space:nowrap;">
                                {{ $c['saldo'] < 0 ? '-' : '' }}Rp {{ number_format(abs($c['saldo']), 0, ',', '.') }}
                            </td>
                            <td align="right" style="padding:8px;border:1px solid #e0e0e0;white-space:nowrap;">Rp {{ number_format($c['minimum'], 0, ',', '.') }}</td>
                            <td align="right" style="padding:8px;border:1px solid #e0e0e0;white-space:nowrap;">Rp {{ number_format($c['kurang'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="margin:0;color:#2e7d32;font-weight:bold;">Tidak ada customer yang saldonya di bawah minimum.</p>
            @endif

            @if ($belumDiatur->count())
                <p style="margin:16px 0 0;padding:8px 12px;background:#fff8e1;border-left:4px solid #ffc107;font-size:14px;">
                    {{ $belumDiatur->count() }} customer belum diatur minimum saldonya: {{ $belumDiatur->implode(', ') }}.
                </p>
            @endif

            <p style="margin:24px 0 0;">
                <a href="{{ route('data-pencatatan.index') }}" style="display:inline-block;background:#3f51b5;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:4px;">
                    Buka Pencatatan Data Customer
                </a>
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:12px 24px;background:#f4f6f8;color:#6c757d;font-size:12px;">
            Email otomatis dari Sistem Pencatatan MPS. Penerima dan jadwal dapat diubah di menu Pencatatan Data Customer → Notifikasi Email.
        </td>
    </tr>
</table>
</body>
</html>
