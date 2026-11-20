---
judul: Membuat Proforma Invoice
kategori: Tagihan
urutan: 33
menu: Invoice & Billing > Tambah Proforma
route: proforma-invoices.select-customer
kata_kunci: proforma, proforma invoice, pi, estimasi tagihan, tagihan awal, penawaran, volume per hari, kurs, mmbtu
ringkasan: Cara membuat proforma invoice (tagihan estimasi di muka), termasuk untuk customer MMBTU, dan mengubah statusnya.
alur: Tambah Proforma | Pilih customer | Isi tanggal & periode | Isi volume & harga | Cek preview | Buat Proforma Invoice
---
Proforma invoice adalah **tagihan estimasi** sebelum pemakaian terjadi. Angkanya diinput manual, tidak diambil dari data pencatatan.

## Langkah-langkah

1. Buka **Invoice & Billing → Tambah Proforma**.
2. Klik customer yang dimaksud. Di sisi kiri tampil **saldo customer** saat ini.
3. Cek **Nomor Proforma**. Bisa diedit, atau klik tombol refresh untuk membuat nomor otomatis.
4. Isi **Tanggal Proforma**, **Tanggal Jatuh Tempo**, dan **Berlaku Sampai** (opsional).
5. Cek **No. Kontrak** dan **ID Pelanggan** (terisi dari data customer).
6. Isi perhitungan sesuai jenis customer:

### Customer biasa

- **Tanggal Mulai** dan **Tanggal Selesai** periode (maksimal 60 hari).
- **Volume Pemakaian (Sm³) / Hari** dan **Harga Satuan (Rp)**.
- **Total Hari** dihitung otomatis (tanggal akhir tidak termasuk).
- Rumus: **Total = Volume per hari × Total hari × Harga**.

### Customer MMBTU

- **Volume (MMBTU)**, **Harga Satuan (USD/MMBTU)**, dan **Kurs USD (Rp)**.
- Tidak ada periode tanggal.
- Rumus: **Total USD = Volume × Harga**, lalu **Total IDR = Total USD × Kurs**.
- Saldo yang tampil dalam MMBTU dan USD.

7. Cek **Preview Perhitungan**, isi **Deskripsi** bila perlu, lalu klik **Buat Proforma Invoice**.

## Status proforma

Ubah status lewat tombol edit: **Draft** (baru dibuat), **Sent** (sudah dikirim), **Expired** (lewat masa berlaku), atau **Converted** (sudah jadi invoice). Daftar proforma bisa difilter per status di **Invoice & Billing → Proforma Invoice**.
