---
judul: Memantau Sisa Saldo & Minimum Saldo Customer
kategori: Customer & Pencatatan
urutan: 18
menu: Pencatatan Data Customer
route: data-pencatatan.index
peran: admin, superadmin, keuangan, staff, staff_operasional
kata_kunci: saldo, sisa saldo, minimum saldo, saldo minimum, batas saldo, peringatan saldo, warning, tidak ada deposit, tanpa deposit, saldo minus, deposit habis
ringkasan: Cara melihat sisa saldo semua customer sekaligus, mengatur minimum saldo, dan membaca peringatan saldo.
alur: Buka Pencatatan Data Customer | Lihat Sisa Saldo & Status | Klik Atur di kolom Minimum Saldo | Isi nominal atau pilih Tidak ada deposit | Simpan
---
Tabel **Daftar Customer** di menu **Pencatatan Data Customer** menampilkan sisa saldo setiap customer dan memberi peringatan kalau saldonya menipis.

## Kolom tabel

| Kolom | Isi |
|---|---|
| Sisa Saldo | Total deposit − total pemakaian (sama dengan "Saldo" di halaman detail customer). Merah bila minus. |
| Minimum Saldo | Batas bawah saldo yang diatur untuk customer itu. |
| Status | **Di bawah minimum**, **Belum diatur**, **Aman**, atau **Tanpa deposit**. |

Minimum saldo **khusus customer Rupiah** dan nominalnya dalam **Rupiah**. Customer **MMBTU** tetap tercantum dengan saldo USD dan MMBTU sebagai informasi, tetapi tidak punya minimum saldo dan tidak memunculkan peringatan (status *MMBTU · tidak dipantau*).

Tabel otomatis mengurutkan yang perlu perhatian di atas: **di bawah minimum**, lalu **belum diatur**.

## Peringatan

- **Merah** di atas tabel: daftar customer yang saldonya **di bawah minimum**. Barisnya juga berwarna merah dengan keterangan kurang berapa. Klik nama customer untuk membuka detailnya.
- **Kuning**: daftar customer yang **belum diatur** minimum saldonya.

## Mengatur minimum saldo

Hanya **Admin, Superadmin, dan Keuangan** yang dapat mengatur.

1. Klik tombol **Atur** di kolom **Minimum Saldo** pada baris customer.
2. Pilih salah satu:
   - **Minimum saldo**: isi nominalnya dalam Rupiah (angka bulat).
   - **Tidak ada deposit**: untuk customer yang memang tidak memakai deposit sehingga saldonya minus. Customer ini tidak akan memunculkan peringatan.
3. Klik **Simpan**.
