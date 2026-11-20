---
judul: Customer MMBTU: Bedanya dengan Customer Biasa
kategori: Customer & Pencatatan
urutan: 16
menu: Kelola User & Pencatatan Data Customer
route: user.update-pricing-mmbtu
kata_kunci: mmbtu, usd, dolar, dollar, kurs, pembagi, konversi, customer mmbtu, saldo mmbtu
ringkasan: Customer MMBTU dihitung dalam MMBTU dan USD. Ini penjelasan perhitungan, deposit, saldo, dan tagihannya.
alur: Buat user role Customer MMBTU | Atur harga USD/MMBTU & pembagi | Catat deposit MMBTU | Input pencatatan | Buat proforma/invoice
---
## Rantai perhitungan

**M³ (flow meter) × koreksi meter = Sm³ → ÷ pembagi Sm³→MMBTU = MMBTU → × harga USD/MMBTU = USD**

## Yang berbeda dari customer biasa

| Bagian | Customer biasa | Customer MMBTU |
|---|---|---|
| Harga | Rp per m³ | USD per MMBTU + pembagi Sm³→MMBTU |
| Deposit | Rupiah | Volume MMBTU × harga satuan USD |
| Saldo | Rupiah | MMBTU dan USD |
| Proforma | Volume per hari × hari × harga | Volume MMBTU × harga USD × kurs |

## Langkah menyiapkan customer MMBTU

1. Buat user dengan role **Customer MMBTU** di *Kelola User*.
2. Atur harga di detail customer: **Harga per MMBTU (USD)** dan **Pembagi SM3 → MMBTU**.
3. Catat deposit dalam MMBTU (lihat *Menambah Deposit*).
4. Input pencatatan meter seperti biasa. Sistem otomatis mengonversinya ke MMBTU dan USD.
