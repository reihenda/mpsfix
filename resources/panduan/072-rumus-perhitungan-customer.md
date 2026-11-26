---
judul: Rumus Perhitungan Pemakaian, Tagihan & Saldo
kategori: Untuk Customer
urutan: 72
menu: Dashboard, Invoice Saya, Billing Saya
route: customer.dashboard, data-pencatatan.customer-detail
peran: customer, admin, superadmin, keuangan, staff, staff_operasional
kata_kunci: rumus, perhitungan, hitung manual, excel, koreksi meter, sm3, tagihan, biaya, saldo, pembulatan, bulat, cara hitung, invoice, billing, proforma, tekanan, suhu
ringkasan: Rumus lengkap dari angka meter sampai tagihan dan saldo, aturan pembulatan, dan cara menghitung ulang sendiri di Excel.
alur: Meter akhir − meter awal | × koreksi meter = Sm³ | × harga per Sm³ = biaya | Jumlahkan sebulan | Bulatkan ke rupiah
---
Halaman ini menjelaskan **persis** bagaimana sistem menghitung pemakaian gas, tagihan, dan saldo Anda, sehingga Anda dapat mencocokkannya sendiri, misalnya di Excel.

## 1. Volume meter (m³)

Setiap pencatatan berisi angka meter awal dan meter akhir.

**Volume meter = Meter akhir − Meter awal**

## 2. Koreksi meter

Gas diukur pada tekanan dan suhu tertentu, sehingga volume meter dikonversi ke kondisi standar (Sm³) dengan **koreksi meter**. Nilainya dihitung dari **tekanan keluar (P, dalam bar)** dan **suhu (T, dalam °C)** yang berlaku pada periode tersebut:

| Komponen | Rumus |
|---|---|
| A (koreksi tekanan) | (P + 1,01325) ÷ 1,01325 |
| B (koreksi suhu) | 300 ÷ (T + 273) |
| C (faktor kompresibilitas) | 1 + 0,002 × P |
| **Koreksi meter** | **A × B × C** |

> **Penting:** sistem menghitung dengan koreksi meter **8 angka di belakang koma**. Di layar hanya ditampilkan 4 angka. Jika Anda memakai angka yang tampil (4 desimal), hasilnya bisa berbeda sedikit.

## 3. Volume standar (Sm³) dan biaya per pencatatan

**Sm³ = Volume meter × Koreksi meter**

**Biaya = Sm³ × Harga per Sm³**

Tekanan, suhu, dan harga yang dipakai adalah milik **periode tanggal pembacaan awal** pencatatan itu:

- Biasanya berlaku satu harga untuk satu bulan.
- Jika ada **periode khusus** (harga berubah di tengah bulan), pencatatan pada rentang tanggal itu memakai harga periode khusus.
- Pencatatan masuk ke bulan **sesuai tanggal pembacaan awalnya**, bukan tanggal pembacaan akhir.
- Jika harga suatu bulan belum diatur, biaya bulan itu dihitung Rp 0 sampai harganya diisi oleh MPS.

## 4. Tagihan bulanan (invoice)

1. Hitung **biaya setiap pencatatan** dengan harga periodenya masing-masing.
2. **Jumlahkan** seluruh biaya pencatatan dalam bulan itu (tanpa dibulatkan dulu).
3. **Bulatkan** hasil akhirnya ke rupiah penuh (0,5 ke atas).

Jika dalam satu bulan ada lebih dari satu harga, invoice menampilkan satu baris per harga.

## 5. Saldo dan billing

Deposit adalah pembayaran di muka. Pemakaian mengurangi saldo.

| | Rumus |
|---|---|
| Saldo keseluruhan | Total deposit − Total biaya pemakaian |
| Saldo bulan ini | Saldo bulan lalu + Deposit bulan ini − Biaya pemakaian bulan ini |
| Harus dibayar (billing) | Jika saldo bulan ini **minus**, sebesar nilai minusnya; jika tidak, Rp 0 |

Saldo bulan lalu dihitung dari **seluruh** deposit dan pemakaian sampai akhir bulan sebelumnya.

## 6. Proforma invoice (estimasi)

**Total = Volume per hari × Jumlah hari × Harga per Sm³**, lalu dibulatkan ke rupiah penuh.

Jumlah hari = selisih tanggal mulai dan tanggal selesai (tanggal selesai tidak ikut dihitung). Contoh: 1–31 Maret = 30 hari.

## 7. Aturan pembulatan

| Nilai | Dipakai dalam hitungan | Ditampilkan |
|---|---|---|
| Koreksi meter | 8 desimal | 4 desimal |
| Volume meter, Sm³ | tidak dibulatkan | 2 desimal |
| Biaya per pencatatan | tidak dibulatkan | 2 desimal |
| Total invoice | dibulatkan ke rupiah penuh | rupiah penuh |
| Saldo, billing | tidak dibulatkan | rupiah penuh atau 2 desimal |

Sistem **tidak membulatkan di tengah perhitungan**. Pembulatan hanya dilakukan pada total akhir invoice dan pada tampilan angka.

## 8. Contoh lengkap

Misalkan tekanan 2 bar, suhu 30 °C, dan harga Rp 12.000 per Sm³.

**Koreksi meter:**
- A = (2 + 1,01325) ÷ 1,01325 = 2,9738465334
- B = 300 ÷ (30 + 273) = 0,9900990099
- C = 1 + 0,002 × 2 = 1,004
- **Koreksi = 2,95618012** (tampil 2,9562)

| No | Meter awal | Meter akhir | Volume meter | Sm³ | Biaya |
|---|---|---|---|---|---|
| 1 | 12.345,678 | 13.596,178 | 1.250,500 | 3.696,703240 | Rp 44.360.438,88 |
| 2 | 13.596,178 | 14.576,428 | 980,250 | 2.897,795563 | Rp 34.773.546,75 |
| 3 | 14.576,428 | 15.682,178 | 1.105,750 | 3.268,796168 | Rp 39.225.554,01 |
| | | | **Total** | **9.863,294970** | **Rp 118.359.539,64** |

**Total invoice = Rp 118.359.540** (dibulatkan ke rupiah penuh).

Kalau koreksi yang dipakai 2,9562 (4 desimal), baris 1 menjadi 3.696,728 Sm³ dan biayanya sekitar Rp 298 lebih besar. Karena itu gunakan koreksi 8 desimal atau hitung dari rumusnya.

## 9. Menghitung ulang di Excel

Susun kolom seperti ini (baris 5 = pencatatan pertama):

| Sel | Isi |
|---|---|
| B1 | Tekanan P (bar), mis. 2 |
| B2 | Suhu T (°C), mis. 30 |
| B3 | Koreksi: `=ROUND(((B1+1.01325)/1.01325)*(300/(B2+273))*(1+0.002*B1),8)` |
| B4 | Harga per Sm³, mis. 12000 |
| B5, C5 | Meter awal, meter akhir |
| D5 | Volume meter: `=C5-B5` |
| E5 | Sm³: `=D5*$B$3` |
| F5 | Biaya: `=E5*$B$4` |
| Total | `=ROUND(SUM(F5:F35),0)` |

- Excel berbahasa Indonesia memakai **titik koma** (`;`) sebagai pemisah dan **koma** untuk desimal. Contoh: `=ROUND(SUM(F5:F35);0)` dan `1,01325`.
- **Jangan menjumlahkan angka yang sudah dibulatkan.** Jumlahkan biaya mentah, lalu bulatkan totalnya sekali.
- Format angka di **Dashboard** memakai `1,234.56` (koma = ribuan), sedangkan di **invoice/billing** memakai `1.234,56` (titik = ribuan).

## 10. Jika hasil Excel Anda berbeda

Periksa hal-hal ini:

1. Koreksi meter memakai 8 desimal, bukan 4.
2. Yang dijumlahkan adalah angka mentah, bukan angka tampilan yang sudah dibulatkan.
3. Harga, tekanan, dan suhu sesuai periodenya, termasuk periode khusus bila ada.
4. Pencatatan dikelompokkan per bulan berdasarkan **tanggal pembacaan awal**.
5. Di **invoice**, dua pencatatan dengan waktu pembacaan awal yang persis sama dihitung **satu kali** saja (dianggap data ganda).

Jika masih berbeda, hubungi tim MPS dengan menyebutkan bulan dan selisihnya.
