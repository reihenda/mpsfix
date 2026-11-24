---
judul: Customer MMBTU: Bedanya dengan Customer Biasa
kategori: Customer & Pencatatan
urutan: 16
menu: Kelola User & Pencatatan Data Customer
route: user.update-pricing-mmbtu
kata_kunci: mmbtu, usd, dolar, dollar, kurs, pembagi, konversi, customer mmbtu, saldo mmbtu, rupiah, kurs historis
ringkasan: Customer MMBTU dihitung dalam MMBTU dan USD; Rupiah hanya untuk internal. Ini penjelasan perhitungan, deposit, saldo, dan tagihannya.
alur: Buat user role Customer MMBTU | Atur harga USD/MMBTU & pembagi | Isi kurs USD bulanan | Catat deposit + Rupiah diterima | Input pencatatan | Buat proforma/invoice
---
## Rantai perhitungan

**M³ (flow meter) × koreksi meter = Sm³ → ÷ pembagi Sm³→MMBTU = MMBTU → × harga USD/MMBTU = USD**

## Siapa melihat apa

- **Customer** hanya melihat **MMBTU** dan **USD** (dashboard, invoice, billing).
- **Internal** (admin, keuangan, staff) melihat **MMBTU**, **USD**, dan **Rupiah**.

## Yang berbeda dari customer biasa

| Bagian | Customer biasa | Customer MMBTU |
|---|---|---|
| Harga | Rp per m³ | USD per MMBTU + pembagi Sm³→MMBTU |
| Deposit | Rupiah | Volume MMBTU × harga satuan USD (+ Rupiah diterima, internal) |
| Saldo | Rupiah | MMBTU dan USD (+ Rupiah, internal) |
| Invoice & billing | Rupiah | USD |
| Proforma | Volume per hari × hari × harga | Volume MMBTU × harga USD × kurs |

## Rupiah (khusus internal)

Rupiah dihitung dengan **kurs historis**:

- **Deposit**: Rupiah yang benar-benar diterima (diisi admin saat mencatat deposit). Kalau dikosongkan, dipakai USD × kurs bulan deposit.
- **Pemakaian**: USD × kurs bulan pemakaian (dari menu *Kurs USD Bulanan*).
- **Saldo Rp historis** = Rupiah deposit − Rupiah pemakaian.
- **Setara hari ini** = saldo USD × kurs terbaru. Selisih keduanya adalah **selisih kurs**.

Kalau kurs suatu bulan belum diisi, sistem sementara memakai kurs bulan terdekat sebelumnya dan menampilkan peringatan di halaman detail customer.

## Langkah menyiapkan customer MMBTU

1. Buat user dengan role **Customer MMBTU** di *Kelola User*.
2. Atur harga di detail customer: **Harga per MMBTU (USD)** dan **Pembagi SM3 → MMBTU**.
3. Pastikan **kurs USD bulanan** sudah diisi (menu *Keuangan → Kurs USD Bulanan*).
4. Catat deposit dalam MMBTU, dan isi **Rupiah Diterima** sesuai bukti transfer.
5. Input pencatatan meter seperti biasa; sistem otomatis menghitung MMBTU, USD, dan Rupiah.
