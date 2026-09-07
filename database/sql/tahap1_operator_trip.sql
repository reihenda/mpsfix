-- Tahap 1: Operator Trip / Absen Trip feature
-- Setara dengan migration: 2026_08_01_000001..000004
-- Jalankan berurutan dari atas ke bawah pada database hosting (sudah punya tabel users, operator_gtm, operator_gtm_lembur).

-- 1) Tambah role 'operator' + kolom operator_gtm_id ke tabel users
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'superadmin', 'customer', 'fob', 'demo', 'keuangan', 'staff', 'mmbtu', 'operator') DEFAULT 'customer';

ALTER TABLE `users` ADD COLUMN `operator_gtm_id` bigint unsigned DEFAULT NULL AFTER `id`;
ALTER TABLE `users` ADD KEY `users_operator_gtm_id_foreign` (`operator_gtm_id`);
ALTER TABLE `users` ADD CONSTRAINT `users_operator_gtm_id_foreign` FOREIGN KEY (`operator_gtm_id`) REFERENCES `operator_gtm` (`id`) ON DELETE SET NULL;

-- 2) Tabel trip_lokasi (master lokasi custom yang diajukan driver)
CREATE TABLE `trip_lokasi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `diajukan_oleh_user_id` bigint unsigned DEFAULT NULL,
  `approved_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_lokasi_diajukan_oleh_user_id_foreign` (`diajukan_oleh_user_id`),
  KEY `trip_lokasi_approved_by_user_id_foreign` (`approved_by_user_id`),
  CONSTRAINT `trip_lokasi_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trip_lokasi_diajukan_oleh_user_id_foreign` FOREIGN KEY (`diajukan_oleh_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Tabel operator_trip (catatan tiap trip/perjalanan driver)
CREATE TABLE `operator_trip` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operator_gtm_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `nopol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asal_type` enum('alamat_pengambilan','customer','trip_lokasi') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asal_id` bigint unsigned DEFAULT NULL,
  `asal_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tujuan_type` enum('alamat_pengambilan','customer','trip_lokasi') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tujuan_id` bigint unsigned DEFAULT NULL,
  `tujuan_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tekanan` decimal(8,2) DEFAULT NULL,
  `foto_berangkat_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude_berangkat` decimal(10,7) DEFAULT NULL,
  `longitude_berangkat` decimal(10,7) DEFAULT NULL,
  `waktu_berangkat` timestamp NULL DEFAULT NULL,
  `foto_sampai_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude_sampai` decimal(10,7) DEFAULT NULL,
  `longitude_sampai` decimal(10,7) DEFAULT NULL,
  `waktu_sampai` timestamp NULL DEFAULT NULL,
  `status` enum('berangkat','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'berangkat',
  `operator_gtm_lembur_id` bigint unsigned DEFAULT NULL,
  `sesi_ke` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_trip_operator_gtm_id_foreign` (`operator_gtm_id`),
  KEY `operator_trip_user_id_foreign` (`user_id`),
  KEY `operator_trip_operator_gtm_lembur_id_foreign` (`operator_gtm_lembur_id`),
  CONSTRAINT `operator_trip_operator_gtm_id_foreign` FOREIGN KEY (`operator_gtm_id`) REFERENCES `operator_gtm` (`id`) ON DELETE CASCADE,
  CONSTRAINT `operator_trip_operator_gtm_lembur_id_foreign` FOREIGN KEY (`operator_gtm_lembur_id`) REFERENCES `operator_gtm_lembur` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operator_trip_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Tabel operator_trip_perbaikan (pengajuan perbaikan trip saat sistem offline)
CREATE TABLE `operator_trip_perbaikan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operator_gtm_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `nopol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asal_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tujuan_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tekanan` decimal(8,2) DEFAULT NULL,
  `foto_bukti_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waktu_klaim` timestamp NULL DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `sesi_ke` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_trip_perbaikan_operator_gtm_id_foreign` (`operator_gtm_id`),
  KEY `operator_trip_perbaikan_user_id_foreign` (`user_id`),
  KEY `operator_trip_perbaikan_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  CONSTRAINT `operator_trip_perbaikan_operator_gtm_id_foreign` FOREIGN KEY (`operator_gtm_id`) REFERENCES `operator_gtm` (`id`) ON DELETE CASCADE,
  CONSTRAINT `operator_trip_perbaikan_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operator_trip_perbaikan_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) OPSIONAL: kalau setelah upload SQL ini Anda juga akan menjalankan `php artisan migrate` di hosting
-- (karena file migration-nya ikut ter-deploy bersama kode), tandai ke-4 migration ini sebagai "sudah jalan"
-- supaya Laravel tidak mencoba membuat ulang tabel yang sama. Sesuaikan nilai `batch` dengan batch
-- terakhir di tabel `migrations` hosting Anda (lihat: SELECT MAX(batch) FROM migrations;).
--
-- INSERT INTO `migrations` (`migration`, `batch`) VALUES
-- ('2026_08_01_000001_add_operator_role_to_users_table', <BATCH_TERAKHIR>),
-- ('2026_08_01_000002_create_trip_lokasi_table', <BATCH_TERAKHIR>),
-- ('2026_08_01_000003_create_operator_trip_table', <BATCH_TERAKHIR>),
-- ('2026_08_01_000004_create_operator_trip_perbaikan_table', <BATCH_TERAKHIR>);
