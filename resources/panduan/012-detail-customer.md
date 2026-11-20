---
judul: Membaca Halaman Detail Customer & Saldo
kategori: Customer & Pencatatan
urutan: 12
menu: Pencatatan Data Customer > pilih customer
route: data-pencatatan.customer-detail
kata_kunci: detail customer, saldo, sisa saldo, riwayat pencatatan, filter periode, pemakaian bulanan, history deposit, total pembelian
ringkasan: Penjelasan isi halaman detail customer: informasi customer, filter periode, saldo, riwayat pencatatan, dan history deposit.
alur: Buka Pencatatan Data Customer | Klik customer | Pilih bulan & tahun | Baca saldo dan riwayat
---
## Membuka detail customer

1. Buka menu **Pencatatan Data Customer**.
2. Di tabel **Daftar Customer**, klik customer yang ingin dilihat. Customer FOB ada di tabel **Daftar FOB**.

## Isi halaman

- **Informasi Customer**: total pemakaian, total deposit, total pembelian, dan saldo keseluruhan.
- **Filter Periode**: pilih **Bulan** dan **Tahun**, lalu klik **Terapkan Filter** untuk melihat data bulan tertentu.
- **Informasi Periode Tahunan** dan **Informasi Periode**: pemakaian dan biaya untuk tahun/bulan yang dipilih, termasuk harga, tekanan (Bar), dan suhu (°C) yang berlaku.
- **Saldo real-time**: saldo hanya untuk periode yang dipilih (saldo bulan lalu + deposit bulan ini − pemakaian bulan ini).
- **Riwayat Pencatatan**: semua pembacaan meter di periode itu, dengan volume meter, Sm³ (dan MMBTU untuk customer MMBTU), serta biayanya.
- **History Deposit**: semua deposit dan pengurangan saldo.

## Cara saldo dihitung

**Saldo = total deposit − total pemakaian.**

- Customer biasa: dalam **Rupiah**.
- Customer MMBTU: dalam **MMBTU** dan **USD**.

Kalau saldo minus, artinya pemakaian sudah melebihi deposit dan customer perlu menambah deposit.

> Yang bisa mengubah harga dan deposit hanya Admin/Superadmin. Role lain hanya bisa melihat.
