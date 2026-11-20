---
judul: Mengelola User (Tambah, Edit, Hapus)
kategori: Customer & Pencatatan
urutan: 10
menu: Kelola User
route: user.index
kata_kunci: user, akun, customer baru, tambah customer, login, password, role, admin, fob, mmbtu, staff, operator, akun driver
ringkasan: Cara menambah customer, FOB, customer MMBTU, staff, atau akun login driver, serta mengubah dan menghapusnya.
alur: Buka Kelola User | Klik Tambah User | Pilih role & isi data | Simpan
---
## Menambah user baru

1. Buka menu **Kelola User**.
2. Klik tombol **Tambah User**.
3. Isi **Nama**, **Email** (dipakai untuk login), dan pilih **Role**:
   - **Customer**: customer biasa, saldo dan harga dalam Rupiah per Sm³.
   - **FOB**: customer yang mengambil gas sendiri (pencatatan per pengambilan).
   - **Customer MMBTU**: customer yang harga dan saldonya dalam MMBTU dan USD.
   - **Admin**, **Keuangan**, **Staff**, **Staff Operasional**, **Demo**: akun internal.
   - **Operator GTM (Driver)**: akun login driver untuk Absen Trip. Pilih **Profil Operator GTM** yang mau dihubungkan (hanya operator yang belum punya akun yang muncul).
4. Untuk customer, isi juga **No. Kontrak**, **Alamat**, dan **Nomor Telepon**. Data ini otomatis dipakai di invoice dan proforma.
5. Isi **Password** (minimal 3 karakter), lalu klik **Simpan**.

## Mengubah user

Klik tombol edit di baris user, ubah datanya, lalu klik **Update**. Kosongkan kolom password kalau tidak ingin mengganti password.

## Menghapus user

Klik tombol hapus, lalu konfirmasi. **Hati-hati:** menghapus customer juga menghapus data pencatatan, billing, dan proforma invoice miliknya, dan tidak bisa dibatalkan. User superadmin tidak bisa dihapus.

> Harga dan deposit customer tidak diatur di sini, tapi di halaman detail customer (menu *Pencatatan Data Customer*).
