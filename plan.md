# Rencana Implementasi: Role `customer_mmbtu`

## Ringkasan

Menambahkan role baru `customer_mmbtu` yang mirip dengan customer biasa, namun dengan perbedaan:
- **Proforma Invoice**: tanpa tgl mulai/selesai, input volume MMBTU + harga USD + kurs USD
- **Data Pencatatan**: saldo dalam MMBTU, harga dalam USD, tabel volume ada 3 kolom (m3, sm3, mmbtu)
- **Deposit**: input dalam MMBTU, bukan Rupiah
- **Atur Harga**: harga per MMBTU (USD) + pembagi SM3 ke MMBTU

## Pendekatan: Conditional Rendering (bukan controller terpisah)

Customer MMBTU menggunakan data pipeline yang sama dengan customer biasa (pembacaan awal/akhir, volume flowmeter, tabel data_pencatatan). Perbedaan hanya di konversi dan tampilan. Maka kita gunakan conditional `@if($customer->isCustomerMmbtu())` di view yang ada, bukan buat controller/view terpisah seperti FOB.

---

## FASE 1: Database & Migration

### 1.1 Migration: Tambah role `customer_mmbtu` ke enum
**File baru:** `database/migrations/2026_03_11_000001_add_customer_mmbtu_role_to_users_table.php`

```sql
ALTER TABLE users MODIFY COLUMN role ENUM('admin','superadmin','customer','fob','demo','keuangan','staff','customer_mmbtu') DEFAULT 'customer'
```

### 1.2 Migration: Tambah kolom MMBTU di proforma_invoices
**File baru:** `database/migrations/2026_03_11_000002_add_mmbtu_columns_to_proforma_invoices.php`

Tambah kolom:
- `volume_mmbtu` DECIMAL(12,6) NULLABLE
- `harga_satuan_usd` DECIMAL(12,4) NULLABLE
- `kurs_usd` DECIMAL(15,2) NULLABLE
- `total_amount_usd` DECIMAL(12,4) NULLABLE

Ubah `period_start_date` dan `period_end_date` menjadi NULLABLE (karena MMBTU tidak pakai).

---

## FASE 2: Model Updates

### 2.1 User Model (`app/Models/User.php`)
- Tambah `isCustomerMmbtu()` method
- Tambah `addPricingHistoryMmbtu()` - simpan pricing dengan field: `harga_per_mmbtu` (USD), `pembagi_sm3_ke_mmbtu`, tekanan, suhu, koreksi_meter
- Update `getPricingForYearMonth()` - tambah branch untuk MMBTU yang return field MMBTU-specific
- Tambah `addDepositMmbtu($mmbtuAmount, $hargaSatuanUsd, $description, $date)` - simpan deposit dalam MMBTU
- Update `isCustomerOrFOB()` atau buat `isAnyCustomerType()` yang include customer_mmbtu

### 2.2 ProformaInvoice Model (`app/Models/ProformaInvoice.php`)
- Tambah `volume_mmbtu`, `harga_satuan_usd`, `kurs_usd`, `total_amount_usd` ke `$fillable` dan `$casts`

---

## FASE 3: Middleware & Routes

### 3.1 CheckRole Middleware (`app/Http/Middleware/CheckRole.php`)
- Tambah case `customer_mmbtu`

### 3.2 Routes (`routes/web.php`)
- Tambah `customer_mmbtu` ke semua middleware group yang ada `customer` dan `fob`
- Tambah route baru untuk pricing MMBTU: `user.update-pricing-mmbtu`
- Tambah route baru untuk deposit MMBTU: `customer-mmbtu.add-deposit`

---

## FASE 4: Controller Updates

### 4.1 UserController (`app/Http/Controllers/UserController.php`)
- Tambah method `updateCustomerMmbtuPricing()` - validasi & simpan harga_per_mmbtu, pembagi_sm3_ke_mmbtu, dll
- Tambah method `addDepositMmbtu()` - validasi & simpan deposit dalam MMBTU
- Tambah method `rekalkulasiTotalPembelianMmbtu()` - hitung ulang total MMBTU consumed
- Update query `whereIn('role', ['customer', 'fob'])` tambahkan `'customer_mmbtu'`

### 4.2 DataPencatatanController (`app/Http/Controllers/DataPencatatanController.php`)
- Update `customerDetail()`: tambah perhitungan MMBTU & USD untuk customer MMBTU
  - Rantai konversi: m3 (flowmeter) -> SM3 (* koreksi_meter) -> MMBTU (/ pembagi) -> USD (* harga_per_mmbtu)
  - Saldo dihitung dalam MMBTU (deposit MMBTU - consumed MMBTU)
- Pass variabel tambahan ke view: `$isMmbtu`, `$filteredVolumeMmbtu`, `$filteredTotalPurchasesUsd`, dll

### 4.3 ProformaInvoiceController (`app/Http/Controllers/ProformaInvoiceController.php`)
- Update `create()`: pass `$isMmbtu` flag ke view
- Update `store()`: conditional validation untuk MMBTU (volume_mmbtu, harga_satuan_usd, kurs_usd). Perhitungan: total_usd = volume * harga, total_rp = total_usd * kurs
- Update `show()`: pass data MMBTU ke view
- Update `update()`: sama dengan store
- Update semua query `whereIn('role')` tambahkan `customer_mmbtu`

### 4.4 DashboardController (`app/Http/Controllers/DashboardController.php`)
- Update dashboard customer untuk conditional MMBTU (label USD, saldo MMBTU)

---

## FASE 5: View Updates

### 5.1 customer-detail.blade.php (perubahan terbesar)

**Summary Cards:**
- Rp -> USD untuk pembelian
- Rp -> MMBTU untuk deposit & saldo
- "Saldo Total" -> "Saldo MMBTU"

**Info Periode:**
- "Harga per Sm3: Rp X" -> "Harga per MMBTU: USD X"
- Tambah "Pembagi SM3 ke MMBTU: X"
- Label Rp -> USD/MMBTU sesuai konteks

**Tabel Riwayat Pencatatan:**
- Header Volume: colspan 2 -> colspan 3 (m3, sm3, MMBTU)
- "Rupiah" -> "USD"
- Tambah kolom MMBTU di body dan footer
- Perhitungan USD: MMBTU * harga_per_mmbtu

**Modal Atur Harga & Koreksi:**
- "Harga per m3 (Rp)" -> "Harga per MMBTU (USD)"
- Tambah field "Pembagi SM3 ke MMBTU"
- Form action -> route MMBTU

**Modal Tambah Deposit:**
- Input: "Jumlah MMBTU" (bukan Rp)
- Tambah input: "Harga Satuan MMBTU (USD)"
- Preview: "Total USD = MMBTU x Harga"
- Form action -> route MMBTU

**Modal Deposit History:**
- Deposit tampil sebagai "X MMBTU (@ USD Y)"
- Saldo dalam MMBTU

**Tabel Saldo Bulan Ini:**
- Semua Rp -> MMBTU/USD sesuai konteks

### 5.2 Proforma Invoice Views

**create.blade.php:**
- Conditional: hide tgl mulai/selesai untuk MMBTU
- Ganti input: Volume MMBTU, Harga USD, Kurs USD
- Preview: Total USD + Total Rp (setelah kurs)
- JavaScript: real-time calculation MMBTU * harga * kurs

**show.blade.php:**
- Conditional: tampilkan Volume MMBTU, Harga USD, Kurs, Total USD, Total Rp

**edit.blade.php:**
- Sama dengan create, conditional form

### 5.3 User Management Views
- Tambah `customer_mmbtu` ke dropdown role di form create/edit user

---

## FASE 6: Update Query Role di Seluruh Controller

File-file yang query `whereIn('role', ['customer', 'fob'])` perlu ditambah `'customer_mmbtu'`:
- `ProformaInvoiceController.php`
- `InvoiceController.php`
- `BillingController.php`
- `DashboardController.php`
- `RekapPengambilanController.php`
- `UserController.php`
- `Rekap/RekapPembelianController.php`
- `Rekap/RekapPenjualanController.php`

---

## Urutan Implementasi

1. Migration (2 file baru)
2. User Model (methods baru + update existing)
3. ProformaInvoice Model (fillable/casts)
4. CheckRole Middleware
5. Routes (web.php)
6. UserController (pricing/deposit MMBTU)
7. DataPencatatanController (customerDetail MMBTU)
8. ProformaInvoiceController (create/store/show/update MMBTU)
9. customer-detail.blade.php (conditional rendering)
10. Proforma views (create/show/edit)
11. Dashboard & user management views
12. Update semua whereIn role queries
13. Run migration & testing

---

## File Baru (2 file)
1. `database/migrations/2026_03_11_000001_add_customer_mmbtu_role_to_users_table.php`
2. `database/migrations/2026_03_11_000002_add_mmbtu_columns_to_proforma_invoices.php`

## File yang Dimodifikasi (~15 file)
1. `app/Models/User.php`
2. `app/Models/ProformaInvoice.php`
3. `app/Http/Middleware/CheckRole.php`
4. `routes/web.php`
5. `app/Http/Controllers/UserController.php`
6. `app/Http/Controllers/DataPencatatanController.php`
7. `app/Http/Controllers/ProformaInvoiceController.php`
8. `app/Http/Controllers/DashboardController.php`
9. `resources/views/data-pencatatan/customer-detail.blade.php`
10. `resources/views/proforma-invoices/create.blade.php`
11. `resources/views/proforma-invoices/show.blade.php`
12. `resources/views/proforma-invoices/edit.blade.php`
13. `resources/views/user/` (form create/edit - dropdown role)
14. Controllers dengan query whereIn role (~6-8 file)
