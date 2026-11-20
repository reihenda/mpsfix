---
judul: Bagaimana Trip GPS Masuk ke Data Lembur
kategori: Operator & Lembur
urutan: 55
menu: Lembur Operator GTM > Trip GPS
route: gps-trip.lembur
kata_kunci: terapkan lembur, trip ke lembur, hitung lembur gps, sinkron lembur, upah lembur gps, lembur manual, bandingkan, ditimpa, timpa, tumpang tindih
ringkasan: Lembur dari Trip GPS terisi otomatis. Ini cara kerjanya, dan cara menangani hari yang sudah punya lembur manual.
alur: Trip dicek & divalidasi | Lembur hari itu dihitung ulang otomatis | Hari dengan lembur manual tidak ditimpa | Bandingkan & pilih | Terapkan ke Lembur
---
## Otomatis

Setiap kali trip berubah (divalidasi, batal validasi, ganti driver, trip diedit atau dihapus, atau foto bukti berhenti lama berubah), lembur driver di hari itu **dihitung ulang otomatis**. Hasilnya langsung terlihat di **Data Lembur** operator.

Trip yang sudah dipakai di data lembur bertanda **sudah di lembur**.

**Trip yang melewati tengah malam** dipecah ke dua hari. Contoh: trip 20:00–04:00 menjadi 20:00–00:00 di hari pertama dan 00:00–04:00 di hari berikutnya.

## Hari yang sudah punya lembur manual

Kalau di hari itu sudah ada data lembur yang **diisi manual**, sistem **tidak menimpanya**. Muncul pemberitahuan berisi daftar hari tersebut dan tautan **bandingkan & pilih mau diganti atau tidak**.

1. Klik tautan itu, atau buka halaman lembur Trip GPS dan pilih **Periode**.
2. Tiap baris menampilkan driver, tanggal, kendaraan, sesi, jam kerja, lembur, upah lembur, dan **Status**:
   - **Baru**: belum ada data lembur di tanggal itu.
   - **Berbeda**: sudah ada data lembur, tapi beda dengan hasil GPS.
   - **Sudah sesuai**: sama persis.
3. Centang hari yang ingin diganti dengan hasil GPS. Hari **berbeda** tidak dicentang otomatis supaya input manual tidak tertimpa tanpa sengaja.
4. Klik **Terapkan ke Lembur**.
