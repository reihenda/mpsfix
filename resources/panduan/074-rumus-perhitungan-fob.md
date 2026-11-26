---
judul: Rumus Perhitungan Customer FOB
kategori: Untuk Customer
urutan: 74
menu: Dashboard
route: fob.dashboard, data-pencatatan.fob-detail
peran: fob, admin, superadmin, keuangan
kata_kunci: rumus, perhitungan, hitung manual, excel, fob, pengambilan, sm3, tagihan, biaya, saldo, pembulatan, cara hitung
ringkasan: Rumus perhitungan biaya pengambilan gas FOB, saldo, aturan pembulatan, dan cara menghitung ulang di Excel.
alur: Volume Sm³ per pengambilan | × harga per Sm³ = biaya | Jumlahkan | Saldo = deposit − biaya
---
Customer FOB dicatat **per pengambilan**. Volume yang dicatat sudah dalam **Sm³**, sehingga **tidak ada koreksi meter**.

## 1. Rumus

| | Rumus |
|---|---|
| Biaya per pengambilan | Volume Sm³ × Harga per Sm³ |
| Biaya sebulan | Jumlah biaya semua pengambilan dalam bulan itu |
| Saldo keseluruhan | Total deposit − Total biaya |
| Saldo bulan ini | Saldo bulan lalu + Deposit bulan ini − Biaya bulan ini |

Harga yang dipakai adalah harga **periode tanggal pengambilan**, termasuk periode khusus bila ada. Jika harga bulan itu belum diatur, biaya dihitung Rp 0 sampai harganya diisi oleh MPS.

## 2. Pembulatan

Sistem **tidak membulatkan di tengah perhitungan**. Volume dan biaya dijumlahkan apa adanya, lalu ditampilkan dengan 2 desimal atau rupiah penuh.

## 3. Contoh

Harga Rp 12.000 per Sm³:

| No | Volume Sm³ | Biaya |
|---|---|---|
| 1 | 2.500,50 | Rp 30.006.000 |
| 2 | 1.980,25 | Rp 23.763.000 |
| | **Total 4.480,75** | **Rp 53.769.000** |

## 4. Menghitung ulang di Excel

| Sel | Isi |
|---|---|
| B1 | Harga per Sm³ |
| B3 | Volume Sm³ pengambilan |
| C3 | Biaya: `=B3*$B$1` |
| Total | `=SUM(C3:C40)` |

Excel berbahasa Indonesia memakai **titik koma** (`;`) dan **koma** desimal.
