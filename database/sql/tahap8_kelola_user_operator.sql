-- Tahap 8: Tambah role Operator GTM ke halaman "Kelola User"
-- Setara dengan migration: 2026_08_01_000006
-- Tidak ada tabel baru, cuma tambah unique index ke kolom users.operator_gtm_id
-- yang sudah dibuat di Tahap 1, supaya 1 profil Operator GTM cuma bisa
-- terhubung ke 1 akun login (dari halaman operator-gtm ATAU dari Kelola User).

ALTER TABLE `users` ADD UNIQUE `users_operator_gtm_id_unique` (`operator_gtm_id`);
