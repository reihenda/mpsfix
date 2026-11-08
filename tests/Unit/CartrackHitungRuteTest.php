<?php

use App\Services\Cartrack\CartrackTripPuller;

function segmenUji(string $mulai, string $selesai, string $dari, string $ke): array
{
    return [
        'driver' => '', 'mulai' => $mulai, 'selesai' => $selesai,
        'lokasi_mulai' => '', 'lokasi_selesai' => '',
        'geofence_mulai' => $dari, 'geofence_selesai' => $ke, 'jarak_km' => 10.0,
    ];
}

$rute = [
    ['id' => 1, 'tujuan' => 'SPBG Cibitung'],
    ['id' => 2, 'tujuan' => 'PT Maju Jaya'],
    ['id' => 3, 'tujuan' => 'SPBG Cibitung'],
];

it('menandai tujuan tercapai berurutan dan tuntas setelah kembali ke tujuan terakhir', function () use ($rute) {
    $segmen = [
        segmenUji('2026-11-08 07:05:00', '2026-11-08 08:30:00', 'SPBG Cibitung', 'PT Maju Jaya'),
        segmenUji('2026-11-08 11:00:00', '2026-11-08 12:20:00', 'PT Maju Jaya', 'SPBG Cibitung'),
    ];

    $hasil = CartrackTripPuller::hitungRute($rute, $segmen, '2026-11-08 07:00:00');

    expect($hasil)->toBe([1 => '2026-11-08 07:05:00', 2 => '2026-11-08 08:30:00', 3 => '2026-11-08 12:20:00']);
});

it('belum menganggap tujuan terakhir tercapai bila kendaraan baru keluar dari titik yang sama', function () use ($rute) {
    $segmen = [segmenUji('2026-11-08 07:05:00', '2026-11-08 08:30:00', 'SPBG Cibitung', 'PT Maju Jaya')];

    $hasil = CartrackTripPuller::hitungRute($rute, $segmen, '2026-11-08 07:00:00');

    expect(array_keys($hasil))->toBe([1, 2]);
});

it('mengabaikan perjalanan yang selesai sebelum jadwal dimulai', function () use ($rute) {
    $segmen = [
        segmenUji('2026-11-08 05:00:00', '2026-11-08 06:00:00', 'PT Maju Jaya', 'SPBG Cibitung'),
        segmenUji('2026-11-08 07:05:00', '2026-11-08 08:30:00', 'SPBG Cibitung', 'PT Maju Jaya'),
    ];

    $hasil = CartrackTripPuller::hitungRute($rute, $segmen, '2026-11-08 07:00:00');

    expect($hasil)->toHaveKeys([1, 2])->and($hasil)->not->toHaveKey(3);
});

it('tidak mencocokkan tujuan yang tidak ada di geofence mana pun', function () {
    $segmen = [segmenUji('2026-11-08 07:05:00', '2026-11-08 08:30:00', 'SPBG Cibitung', 'PT Lain')];

    $hasil = CartrackTripPuller::hitungRute([['id' => 9, 'tujuan' => 'Gudang Tanpa Geofence']], $segmen, '2026-11-08 07:00:00');

    expect($hasil)->toBe([]);
});

it('tidak membuat tujuan kosong cocok dengan semua geofence', function () {
    $segmen = [segmenUji('2026-11-08 07:05:00', '2026-11-08 08:30:00', 'SPBG Cibitung', 'PT Lain')];

    expect(CartrackTripPuller::hitungRute([['id' => 1, 'tujuan' => '   ']], $segmen, '2026-11-08 07:00:00'))->toBe([]);
});
