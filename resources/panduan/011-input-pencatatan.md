---
judul: Input Data Pencatatan Meter Customer
kategori: Customer & Pencatatan
urutan: 11
menu: Input Data Baru
route: data-pencatatan.create
kata_kunci: pencatatan, input data, meter, pembacaan, flow meter, volume, catat pemakaian, stand meter
ringkasan: Cara mencatat pembacaan meter awal dan akhir customer supaya pemakaian dan saldonya terhitung.
alur: Buka Input Data Baru | Pilih customer | Isi pembacaan awal & akhir | Cek volume flow meter | Simpan
---
## Langkah-langkah

1. Buka menu **Input Data Baru** (atau klik tombol input dari halaman detail customer).
2. **Pilih Customer**.
3. Isi **Pembacaan Awal**: waktu dan angka volume meter di awal.
4. Isi **Pembacaan Akhir**: waktu dan angka volume meter di akhir.
5. **Volume Flow Meter** terisi otomatis = volume akhir − volume awal. Pastikan angkanya masuk akal.
6. Klik **Simpan Data**.

## Setelah disimpan

Sistem otomatis menghitung:

- **Sm³** = volume flow meter × koreksi meter periode tersebut.
- **Biaya** = Sm³ × harga per m³ (customer biasa), atau MMBTU × harga USD/MMBTU (customer MMBTU).
- **Saldo** customer langsung berkurang sesuai biaya.

> Pastikan **harga periode** pembacaan itu sudah diatur. Kalau belum, data tetap tersimpan tapi biayanya belum benar, dan bulan itu muncul di notifikasi lonceng sebagai bulan tanpa harga.

## Input banyak data sekaligus

Pakai **Upload Excel** di halaman detail customer. Lihat panduan *Upload Pencatatan dari Excel*.
