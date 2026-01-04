-- Tabel untuk menyimpan data operator GTM
CREATE TABLE `operator_gtm` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `lokasi_kerja` varchar(255) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL DEFAULT '3500000.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan data lembur operator GTM
CREATE TABLE `operator_gtm_lembur` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `operator_gtm_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk_sesi_1` time DEFAULT NULL,
  `jam_keluar_sesi_1` time DEFAULT NULL,
  `jam_masuk_sesi_2` time DEFAULT NULL,
  `jam_keluar_sesi_2` time DEFAULT NULL,
  `total_jam_kerja` int(11) DEFAULT NULL,
  `total_jam_lembur` int(11) DEFAULT NULL,
  `upah_lembur` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_gtm_lembur_operator_gtm_id_foreign` (`operator_gtm_id`),
  CONSTRAINT `operator_gtm_lembur_operator_gtm_id_foreign` FOREIGN KEY (`operator_gtm_id`) REFERENCES `operator_gtm` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk konfigurasi tarif lembur
CREATE TABLE `konfigurasi_lembur` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_konfigurasi` varchar(255) NOT NULL,
  `tarif_per_jam` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default konfigurasi lembur
INSERT INTO `konfigurasi_lembur` (`nama_konfigurasi`, `tarif_per_jam`, `is_active`, `created_at`, `updated_at`) VALUES
('Tarif Lembur Standar', 25000.00, 1, NOW(), NOW());