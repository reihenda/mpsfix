---
judul: Mengisi Kurs USD Bulanan
kategori: Keuangan
urutan: 62
menu: Keuangan > Kurs USD Bulanan
route: kurs-usd.index
kata_kunci: kurs, kurs usd, dolar, dollar, rupiah, kurs historis, kurs bulanan, exchange rate, jisdor, mmbtu, real-time, ecb, frankfurter, rata-rata, akhir bulan, sumber kurs
ringkasan: Cara mengisi kurs USD per bulan yang dipakai menghitung Rupiah pemakaian dan saldo customer MMBTU.
alur: Buka Kurs USD Bulanan | Pilih bulan | Pilih metode & klik Ambil kurs (atau ketik manual) | Cek sumber | Simpan
---
Kurs bulanan dipakai untuk mengubah USD customer MMBTU menjadi **Rupiah** di halaman internal (detail customer, Rekap Penjualan, dashboard admin). Customer tidak melihat Rupiah.

## Mengisi kurs

1. Buka **Keuangan → Kurs USD Bulanan**.
2. Pilih **Bulan**.
3. Isi **Kurs 1 USD (Rp)** secara manual, **atau** ambil otomatis:
   - Pilih metode **Rata-rata bulan** (rata-rata semua kurs harian di bulan itu) atau **Kurs akhir bulan** (kurs hari kerja terakhir).
   - Klik **Ambil kurs [bulan]**. Kurs diambil untuk **bulan yang dipilih**, bukan kurs hari ini, jadi bulan yang sudah lewat tetap mendapat kurs historisnya.
   - Untuk bulan yang sedang berjalan, dipakai data sampai hari ini.
4. Di bawah kolom kurs muncul **catatan sumber**, mis. *"Rata-rata 22 kurs harian 2–31 Mar 2026 · kurs referensi ECB via frankfurter.dev"*. Kalau angka diubah manual, sumber otomatis menjadi **input manual**.
5. Isi **Catatan** bila perlu, lalu klik **Simpan Kurs**.

## Sumber kurs otomatis

Kurs otomatis berasal dari **kurs referensi harian European Central Bank (ECB)**, diambil lewat layanan gratis **frankfurter.dev**. Kurs hanya tersedia untuk hari kerja. Kalau sumber gagal dihubungi, sistem menampilkan pesan gagal dan **tidak mengisi angka apa pun**, sehingga kurs bisa diisi manual.

Di **Riwayat Kurs**, kolom Sumber menampilkan **ECB** (beserta keterangannya) atau **Manual**.

Menyimpan bulan yang sudah ada akan **mengganti** kursnya. Untuk mengubah, klik tombol edit di **Riwayat Kurs**.

## Bulan yang belum ada kurs

Di bagian atas halaman muncul daftar bulan yang punya transaksi customer MMBTU tetapi kursnya belum diisi. Klik bulan itu untuk langsung mengisinya. Sementara belum diisi, Rupiah bulan tersebut memakai kurs bulan terdekat sebelumnya.

## Kurs dipakai di mana

- **Pemakaian** bulan X → kurs bulan X.
- **Deposit** tanpa "Rupiah diterima" → kurs bulan deposit.
- **Setara Rp hari ini** → kurs terbaru.
- **Proforma** customer MMBTU → kolom kurs otomatis terisi kurs terbaru (bisa diubah).
