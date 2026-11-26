---
judul: Rumus Perhitungan Customer MMBTU (MMBTU & USD)
kategori: Untuk Customer
urutan: 73
menu: Dashboard, Invoice Saya, Billing Saya
route: customer.dashboard, data-pencatatan.customer-detail
peran: mmbtu, admin, superadmin, keuangan, staff, staff_operasional
kata_kunci: rumus, perhitungan, hitung manual, excel, mmbtu, usd, dolar, dollar, pembagi, koreksi meter, sm3, tagihan, biaya, saldo, pembulatan, cara hitung, invoice, billing, proforma, kurs
ringkasan: Rumus lengkap dari angka meter ke Sm³, MMBTU, dan USD, termasuk deposit, saldo, aturan pembulatan, dan cara menghitung ulang di Excel.
alur: Meter akhir − meter awal | × koreksi meter = Sm³ | ÷ pembagi = MMBTU | × harga USD/MMBTU = USD | Jumlahkan & bulatkan ke sen
---
Akun Anda dihitung dalam **MMBTU** (energi) dan **USD**. Halaman ini menjelaskan **persis** bagaimana sistem menghitungnya, sehingga Anda dapat mencocokkannya sendiri, misalnya di Excel.

## 1. Rantai perhitungan

| Langkah | Rumus |
|---|---|
| Volume meter (m³) | Meter akhir − Meter awal |
| Volume standar (Sm³) | Volume meter × Koreksi meter |
| Energi (MMBTU) | Sm³ ÷ Pembagi Sm³→MMBTU |
| Biaya (USD) | MMBTU × Harga USD per MMBTU |

**Pembagi Sm³→MMBTU** adalah jumlah Sm³ untuk 1 MMBTU (sesuai nilai kalor gas), ditetapkan per periode dan tercantum di dashboard Anda.

## 2. Koreksi meter

Dihitung dari **tekanan keluar (P, bar)** dan **suhu (T, °C)** periode tersebut:

| Komponen | Rumus |
|---|---|
| A | (P + 1,01325) ÷ 1,01325 |
| B | 300 ÷ (T + 273) |
| C | 1 + 0,002 × P |
| **Koreksi meter** | **A × B × C** |

> Sistem memakai koreksi meter **8 angka di belakang koma**. Di layar hanya ditampilkan 4 angka.

Tekanan, suhu, pembagi, dan harga yang dipakai adalah milik **periode tanggal pembacaan awal** setiap pencatatan. Pencatatan masuk ke bulan sesuai tanggal pembacaan awalnya.

## 3. Deposit dan saldo

Deposit dicatat dalam **MMBTU** dengan harga satuan USD:

**Nilai deposit (USD) = MMBTU deposit × Harga satuan USD**

| | Rumus |
|---|---|
| Saldo MMBTU | Total MMBTU deposit − Total MMBTU pemakaian |
| Saldo USD | Total USD deposit − Total USD pemakaian |
| Saldo bulan ini | Saldo bulan lalu + Deposit bulan ini − Pemakaian bulan ini |
| Harus dibayar (billing) | Jika saldo USD bulan ini **minus**, sebesar nilai minusnya; jika tidak, $0 |

Pengurangan saldo mengurangi MMBTU dan USD dengan cara yang sama (MMBTU × harga satuan).

## 4. Tagihan (invoice & billing)

1. Hitung **USD setiap pencatatan** dengan pembagi dan harga periodenya.
2. **Jumlahkan** seluruh USD dalam periode tagihan (tanpa dibulatkan dulu).
3. **Bulatkan** total akhir ke **2 desimal (sen)**.

## 5. Proforma invoice (estimasi)

**Total USD = Volume MMBTU × Harga USD per MMBTU**, dibulatkan ke 2 desimal.

**Total Rupiah = Total USD (2 desimal) × Kurs**, dibulatkan ke rupiah penuh. Kurs dicantumkan di proforma.

## 6. Aturan pembulatan

| Nilai | Dipakai dalam hitungan | Ditampilkan |
|---|---|---|
| Koreksi meter | 8 desimal | 4 desimal |
| Sm³, MMBTU | tidak dibulatkan | 2 desimal |
| USD per pencatatan | tidak dibulatkan | 2 desimal |
| Total invoice / billing | dibulatkan ke sen (2 desimal) | 2 desimal |
| Saldo MMBTU / USD | tidak dibulatkan | 2 desimal |

Sistem **tidak membulatkan di tengah perhitungan**. Pembulatan hanya dilakukan pada total akhir dan pada tampilan angka.

## 7. Contoh lengkap

Misalkan tekanan 1 bar, suhu 30 °C, pembagi 27,27 Sm³/MMBTU, dan harga $16 per MMBTU.

**Koreksi meter:** A = 1,98692327 · B = 0,99009901 · C = 1,002 → **Koreksi = 1,97118526** (tampil 1,9712)

| No | Volume meter | Sm³ | MMBTU | USD |
|---|---|---|---|---|
| 1 | 1.385,250 | 2.730,584381 | 100,131440 | $1.602,103047 |
| 2 | 1.355,250 | 2.671,448824 | 97,962920 | $1.567,406717 |
| | **Total** | **5.402,033205** | **198,094360** | **$3.169,509765** |

**Total tagihan = $3.169,51** (dibulatkan ke sen).

Jika deposit 500 MMBTU × $16 = $8.000, maka:
- Saldo MMBTU = 500 − 198,094360 = **301,91 MMBTU**
- Saldo USD = 8.000 − 3.169,509765 = **$4.830,49**

## 8. Menghitung ulang di Excel

| Sel | Isi |
|---|---|
| B1 | Tekanan P (bar) |
| B2 | Suhu T (°C) |
| B3 | Koreksi: `=ROUND(((B1+1.01325)/1.01325)*(300/(B2+273))*(1+0.002*B1),8)` |
| B4 | Pembagi Sm³→MMBTU, mis. 27.27 |
| B5 | Harga USD per MMBTU, mis. 16 |
| B7, C7 | Meter awal, meter akhir |
| D7 | Volume meter: `=C7-B7` |
| E7 | Sm³: `=D7*$B$3` |
| F7 | MMBTU: `=E7/$B$4` |
| G7 | USD: `=F7*$B$5` |
| Total | `=ROUND(SUM(G7:G37),2)` |

- Excel berbahasa Indonesia memakai **titik koma** (`;`) dan **koma** desimal. Contoh: `=ROUND(SUM(G7:G37);2)` dan `1,01325`.
- **Jangan menjumlahkan angka yang sudah dibulatkan.** Jumlahkan USD mentah, lalu bulatkan totalnya sekali.
- Dashboard memakai format `1,234.56`, sedangkan invoice/billing memakai `1.234,56`.

## 9. Jika hasil Excel Anda berbeda

1. Koreksi meter memakai 8 desimal, bukan 4.
2. Yang dijumlahkan adalah angka mentah, bukan angka tampilan.
3. Pembagi, harga, tekanan, dan suhu sesuai periodenya.
4. Pencatatan dikelompokkan per bulan berdasarkan **tanggal pembacaan awal**.
5. Di **invoice**, dua pencatatan dengan waktu pembacaan awal yang persis sama dihitung **satu kali** saja.

Jika masih berbeda, hubungi tim MPS dengan menyebutkan bulan dan selisihnya.
