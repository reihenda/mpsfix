---
judul: Import Laporan Trip GPS dari Excel
kategori: Operator & Lembur
urutan: 52
menu: Lembur Operator GTM > Trip GPS > Import Excel
route: gps-trip.upload
kata_kunci: trip gps, upload gps, summary trip report, excel gps, import trip, geofence baru, laporan gps
ringkasan: Cadangan bila data Cartrack tidak tersedia: unggah file "Standard Summary Trip Report" supaya terbentuk trip per kendaraan.
alur: Buka Trip GPS | Upload Excel | Klasifikasikan geofence baru | Simpan & Import Trip | Cek trip
---
Biasanya trip ditarik otomatis dari Cartrack (lihat *Mengecek dan Memvalidasi Trip GPS*). Import Excel dipakai sebagai **cadangan** bila data Cartrack tidak tersedia.

## Langkah-langkah

1. Buka **Lembur Operator GTM → Trip GPS**, lalu klik **Import Excel**.
2. Pilih file laporan GPS **Standard Summary Trip Report** (.xls / .xlsx), lalu klik **Upload & Proses**.
3. Kalau ada **geofence** (nama lokasi dari GPS) yang belum dikenal, sistem menanyakan jenisnya:
   - **Customer/PT**: lokasi customer, tempat trip dimulai dan berakhir.
   - **SPBG** atau **Lainnya**: tempat singgah.
   Kolom customer terdaftar hanya label; yang menentukan trip adalah kolom **Jenis**.
4. Klik **Simpan & Import Trip**.

## Cara trip dibentuk

- Trip **dimulai** saat kendaraan keluar dari geofence customer/PT.
- Trip **berakhir** saat masuk geofence customer/PT (tidak harus yang sama).
- Singgah di SPBG atau lokasi lain tidak memutus trip.

Upload ulang file yang sama tidak membuat trip ganda. **Riwayat Upload** menampilkan file yang pernah diunggah.
