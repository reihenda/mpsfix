---
judul: Gambaran Umum Sistem
kategori: Mulai di Sini
urutan: 1
menu: Panduan Sistem
route: data-pencatatan.index, nomor-polisi.index, keuangan.index, jadwal-perjalanan.index
kata_kunci: alur, sistem, overview, pengenalan, mulai, awal, peran, role, akses
ringkasan: Apa saja isi sistem ini, siapa memakai apa, dan bagaimana data mengalir dari pencatatan sampai tagihan.
alur: Pencatatan meter customer | Harga & deposit customer | Saldo dihitung otomatis | Invoice / Billing / Proforma | Laporan & keuangan
---
Sistem ini dipakai untuk mengelola penjualan gas CNG: mencatat pemakaian gas di tiap customer, menghitung saldo (deposit dikurangi pemakaian), membuat dokumen tagihan, sampai mengelola lembur driver/operator GTM dan keuangan perusahaan.

## Alur besar

1. **Customer dibuat** di menu *Kelola User* (lengkap dengan No. Kontrak, alamat, dan role: Customer, FOB, atau Customer MMBTU).
2. **Harga & koreksi meter** diatur per periode di halaman detail customer.
3. **Deposit** (pembayaran di muka) dicatat di halaman detail customer.
4. **Pembacaan meter** diinput lewat *Input Data Baru* atau upload Excel. Sistem otomatis menghitung volume Sm³, biaya, dan **saldo** customer.
5. Dari data itu dibuat **Invoice**, **Billing**, atau **Proforma Invoice**.
6. Angka penjualan dan pembelian gas otomatis masuk ke **Rekap Penjualan**, **Rekap Pembelian**, dan dashboard.

Di sisi operasional, perjalanan GTM (kendaraan pengangkut gas) dicatat lewat **Absen Trip** driver, **Trip GPS**, dan **Jadwal Perjalanan** (Cartrack), lalu dijadikan **data lembur** operator.

## Siapa memakai apa

| Role | Yang bisa dilakukan |
|---|---|
| Admin / Superadmin | Semua menu: user, pencatatan, harga, deposit, tagihan, rekap, lembur, Trip GPS, keuangan |
| Keuangan | Tagihan (invoice, billing, proforma), keuangan (kas, bank), lihat pencatatan & lembur |
| Staff | Input & kelola pencatatan, Kelola Mobil/NOPOL, Rekap Pengambilan |
| Staff Operasional | Lihat pencatatan & lembur, kelola Jadwal Perjalanan dan Sinkronasi Cartrack |
| Customer / FOB / Customer MMBTU | Lihat pemakaian, saldo, invoice, dan billing miliknya sendiri |
| Operator GTM (driver) | Absen Trip dan pengajuan Perbaikan Trip dari HP |

> Panduan yang muncul di menu ini otomatis disesuaikan dengan menu yang bisa kamu buka. Kalau sebuah menu tidak ada di akunmu, panduannya juga tidak tampil.

## Istilah penting

- **M³ / flow meter**: selisih angka meter akhir dikurangi meter awal.
- **Koreksi meter**: faktor pengali dari tekanan dan suhu. **Sm³ = M³ × koreksi meter.**
- **MMBTU**: satuan energi untuk customer MMBTU. **MMBTU = Sm³ ÷ pembagi Sm³→MMBTU.**
- **Saldo**: total deposit dikurangi total pemakaian. Customer biasa dalam Rupiah, customer MMBTU dalam MMBTU dan USD.
