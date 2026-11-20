---
judul: Jadwal Perjalanan (Cartrack)
kategori: Operator & Lembur
urutan: 56
menu: Jadwal Perjalanan
route: jadwal-perjalanan.index
kata_kunci: jadwal, jadwal perjalanan, rute, tujuan, cartrack, kirim driver, link driver, tarik trip, selesaikan, batalkan
ringkasan: Cara membuat jadwal perjalanan kendaraan dan driver, menghubungkannya ke Cartrack, dan memvalidasi trip hasilnya.
alur: Buat jadwal | Driver dikirim ke Cartrack | Trip ditarik dari Cartrack | Jadwal selesai sesuai rute | Validasi trip
---
## Membuat jadwal

1. Buka **Jadwal Perjalanan**, lalu klik tambah.
2. Pilih **Kendaraan** dan **Driver**. Kalau tidak ada di daftar, gunakan opsi "tidak ada di daftar".
3. Isi waktu **Mulai**.
4. Isi **Rute**: lokasi tujuan berurutan. Klik **Tambah tujuan** untuk menambah.
5. Isi **Catatan jadwal** bila perlu, lalu klik **Simpan jadwal**.

## Setelah disimpan

- Sistem mengirim pasangan driver–kendaraan ke **Cartrack**. Kalau gagal, buka jadwalnya lalu klik **Kirim ulang driver**. Driver harus sudah dipetakan di *Sinkronasi Cartrack dan MPS*.
- Status jadwal: **Terjadwal** → **Berjalan** → **Selesai** (atau **Batal**).
- Jadwal selesai otomatis setelah semua tujuan di rute tercapai. Bisa juga diselesaikan manual dengan **Selesaikan sekarang**.

## Memvalidasi trip

Di bagian **Trip dari Cartrack**, cek tiap trip, lalu pilih **Tandai dihitung** atau **Tandai tidak dihitung**. Hanya trip tervalidasi yang masuk hitungan lembur. **Batalkan validasi** untuk mengulang.

## Membatalkan atau menghapus

Klik **Batalkan** untuk membatalkan jadwal. Jadwal batal bisa dihapus dengan **Hapus jadwal**; trip hasil tarikannya yang belum divalidasi ikut terhapus.
