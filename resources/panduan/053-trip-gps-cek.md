---
judul: Mengecek dan Memvalidasi Trip GPS
kategori: Operator & Lembur
urutan: 53
menu: Lembur Operator GTM > Trip GPS
route: gps-trip.index
kata_kunci: trip gps, validasi, set driver, noise, berhenti lama, bukti foto, potong jam kerja, sinkron cartrack, periode, edit trip
ringkasan: Cara memeriksa trip hasil GPS/Cartrack: menentukan driver, membuang noise, menangani berhenti lama, dan memvalidasi.
alur: Pilih periode | Sinkronkan dari Cartrack | Set driver | Hapus noise | Tangani berhenti lama | Validasi
---
## Filter

Pilih **Periode** (26 bulan lalu s.d. 25), **Kendaraan**, **Driver**, dan status **Validasi**, lalu klik **Terapkan**. Pilihan **Perlu dicek** menampilkan trip yang butuh perhatian.

## Menarik data dari Cartrack

Klik **Sinkronkan periode ini**. Permintaan diproses di latar belakang. Waktu sinkron terakhir tampil di halaman.

## Yang perlu dicek

1. **Driver**: pilih driver di tiap trip, lalu klik **Set Driver**. Trip tanpa driver tidak masuk lembur siapa pun.
2. **Trip noise**: trip di bawah 5 menit, atau kendaraan yang hanya bergeser di dalam area customer, dikumpulkan di panel noise. Centang lalu klik **Hapus yang dicentang**. Noise yang dihapus tidak muncul lagi walau file di-upload ulang.
3. **Berhenti lama di luar geofence**: kendaraan berhenti lama di tengah jalan. Jam kerja **hanya dipotong kalau ada bukti**. Buka **Bukti berhenti lama**, tambah foto (JPG/PNG/WEBP, maks. 5 foto, 8 MB/foto) dan catatan, lalu klik **Unggah & potong jam kerja**.
4. **Edit trip**: klik edit untuk mengubah waktu mulai/selesai, geofence awal/akhir, atau mencentang **Hitung sebagai trip**.

## Validasi

Trip dari Cartrack harus **divalidasi** dulu sebelum masuk hitungan lembur. Klik validasi di trip yang sudah benar. **Batal validasi** mengembalikannya.

## Rekap per driver

Tabel **Rekap per Driver** menampilkan hari kerja, jumlah trip, dan waktu jalan tiap driver untuk trip yang dihitung.
