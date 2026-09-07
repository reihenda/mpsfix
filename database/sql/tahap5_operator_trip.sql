-- Tahap 5: Approval lokasi custom, pengelompokan trip ke sesi, review perbaikan trip
-- Setara dengan migration: 2026_08_01_000005
-- Tidak ada tabel baru di tahap ini, cuma tambah 1 nilai enum ke tabel trip_lokasi yang sudah ada.

ALTER TABLE `trip_lokasi` MODIFY COLUMN `status` ENUM('pending', 'approved', 'ditolak') NOT NULL DEFAULT 'pending';
