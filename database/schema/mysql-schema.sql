/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `alamat_pengambilan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alamat_pengambilan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_alamat` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alamat_pengambilan_nama_alamat_unique` (`nama_alamat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `year` int NOT NULL,
  `month` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_transactions_voucher_number_unique` (`voucher_number`),
  KEY `bank_transactions_account_id_foreign` (`account_id`),
  KEY `bank_transactions_year_month_index` (`year`,`month`),
  KEY `bank_transactions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `bank_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `billing_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_date` date NOT NULL,
  `total_volume` decimal(12,2) NOT NULL,
  `total_amount` decimal(20,2) NOT NULL,
  `total_deposit` decimal(12,2) NOT NULL,
  `previous_balance` decimal(20,2) NOT NULL,
  `current_balance` decimal(20,2) NOT NULL,
  `amount_to_pay` decimal(20,2) NOT NULL,
  `period_month` int NOT NULL,
  `period_year` int NOT NULL,
  `period_type` enum('monthly','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `custom_start_date` date DEFAULT NULL,
  `custom_end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billings_customer_id_foreign` (`customer_id`),
  KEY `billings_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `billings_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_pencatatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_pencatatan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `rekap_pengambilan_id` bigint unsigned DEFAULT NULL,
  `nama_customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_input` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `harga_final` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status_pembayaran` enum('belum_lunas','lunas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_lunas',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data_pencatatan_customer_id_foreign` (`customer_id`),
  KEY `data_pencatatan_rekap_pengambilan_id_foreign` (`rekap_pengambilan_id`),
  KEY `data_pencatatan_customer_id_rekap_pengambilan_id_index` (`customer_id`,`rekap_pengambilan_id`),
  CONSTRAINT `data_pencatatan_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `data_pencatatan_rekap_pengambilan_id_foreign` FOREIGN KEY (`rekap_pengambilan_id`) REFERENCES `rekap_pengambilan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `data_pencatatan_chk_1` CHECK (json_valid(`data_input`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `account_type` enum('kas','bank') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_accounts_account_code_unique` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `harga_gagas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `harga_gagas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `harga_usd` decimal(10,2) NOT NULL COMMENT 'Harga dalam USD',
  `rate_konversi_idr` decimal(10,2) NOT NULL COMMENT 'Rate konversi USD ke IDR',
  `kalori` decimal(20,16) NOT NULL COMMENT 'Nilai kalori untuk konversi ke MMBTU (presisi 12 desimal)',
  `periode_tahun` int NOT NULL COMMENT 'Tahun periode berlaku',
  `periode_bulan` int NOT NULL COMMENT 'Bulan periode berlaku',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_periode_harga_gagas` (`periode_tahun`,`periode_bulan`),
  KEY `harga_gagas_periode_tahun_periode_bulan_index` (`periode_tahun`,`periode_bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `billing_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `id_pelanggan` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(20,2) NOT NULL,
  `total_volume` decimal(12,2) DEFAULT '0.00',
  `no_kontrak` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('paid','unpaid','partial','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `description` text COLLATE utf8mb4_unicode_ci,
  `period_month` int NOT NULL,
  `period_year` int NOT NULL,
  `period_type` enum('monthly','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `custom_start_date` date DEFAULT NULL,
  `custom_end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  KEY `invoices_billing_id_foreign` (`billing_id`),
  CONSTRAINT `invoices_billing_id_foreign` FOREIGN KEY (`billing_id`) REFERENCES `billings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kas_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kas_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `year` int NOT NULL,
  `month` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kas_transactions_voucher_number_unique` (`voucher_number`),
  KEY `kas_transactions_account_id_foreign` (`account_id`),
  KEY `kas_transactions_year_month_index` (`year`,`month`),
  KEY `kas_transactions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `kas_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `konfigurasi_lembur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `konfigurasi_lembur` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_konfigurasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tarif_per_jam` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monthly_customer_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_customer_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `year_month` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: 2024-01',
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_deposits` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_purchases` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_volume_sm3` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Detail perhitungan untuk audit',
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthly_customer_balances_customer_id_year_month_unique` (`customer_id`,`year_month`),
  KEY `monthly_customer_balances_customer_id_year_month_index` (`customer_id`,`year_month`),
  KEY `monthly_customer_balances_year_month_index` (`year_month`),
  KEY `idx_monthly_balances_year_month_balance` (`year_month`,`closing_balance`),
  CONSTRAINT `monthly_customer_balances_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_customer_balances_chk_1` CHECK (json_valid(`calculation_details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomor_polisi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomor_polisi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nopol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ukuran_id` bigint unsigned DEFAULT NULL,
  `area_operasi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_gtm` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('milik','sewa','disewakan','FOB') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso` enum('ISO - 11439','ISO - 11119') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coi` enum('sudah','belum') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_polisi_nopol_unique` (`nopol`),
  KEY `nomor_polisi_ukuran_id_foreign` (`ukuran_id`),
  CONSTRAINT `nomor_polisi_ukuran_id_foreign` FOREIGN KEY (`ukuran_id`) REFERENCES `ukuran` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operator_gtm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operator_gtm` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi_kerja` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL DEFAULT '3500000.00',
  `jam_kerja` int NOT NULL DEFAULT '8',
  `tanggal_bergabung` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operator_gtm_lembur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operator_gtm_lembur` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operator_gtm_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk_sesi_1` time DEFAULT NULL,
  `jam_keluar_sesi_1` time DEFAULT NULL,
  `jam_masuk_sesi_2` time DEFAULT NULL,
  `jam_keluar_sesi_2` time DEFAULT NULL,
  `jam_masuk_sesi_3` time DEFAULT NULL,
  `jam_keluar_sesi_3` time DEFAULT NULL,
  `jam_masuk_sesi_4` time DEFAULT NULL,
  `jam_keluar_sesi_4` time DEFAULT NULL,
  `jam_masuk_sesi_5` time DEFAULT NULL,
  `jam_keluar_sesi_5` time DEFAULT NULL,
  `total_jam_kerja` int DEFAULT NULL,
  `total_jam_lembur` int DEFAULT NULL,
  `upah_lembur` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `operator_gtm_lembur_operator_gtm_id_foreign` (`operator_gtm_id`),
  CONSTRAINT `operator_gtm_lembur_operator_gtm_id_foreign` FOREIGN KEY (`operator_gtm_id`) REFERENCES `operator_gtm` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proforma_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proforma_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `proforma_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proforma_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `total_volume` decimal(10,3) NOT NULL DEFAULT '0.000',
  `volume_mmbtu` decimal(14,4) DEFAULT NULL,
  `price_per_mmbtu_usd` decimal(14,4) DEFAULT NULL,
  `kurs_usd` decimal(14,2) DEFAULT NULL,
  `total_usd` decimal(14,2) DEFAULT NULL,
  `volume_per_day` decimal(10,3) NOT NULL,
  `price_per_sm3` decimal(12,2) NOT NULL,
  `total_days` int NOT NULL,
  `status` enum('draft','sent','expired','converted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `description` text COLLATE utf8mb4_unicode_ci,
  `no_kontrak` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_pelanggan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `validity_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proforma_invoices_customer_id_foreign` (`customer_id`),
  KEY `proforma_invoices_customer_id_proforma_date_index` (`customer_id`,`proforma_date`),
  KEY `proforma_invoices_status_index` (`status`),
  CONSTRAINT `proforma_invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rekap_pengambilan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rekap_pengambilan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` datetime DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `nopol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volume` decimal(10,2) NOT NULL,
  `alamat_pengambilan_id` bigint unsigned DEFAULT NULL,
  `alamat_pengambilan` text COLLATE utf8mb4_unicode_ci,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rekap_pengambilan_customer_id_foreign` (`customer_id`),
  KEY `rekap_pengambilan_nopol_foreign` (`nopol`),
  KEY `rekap_pengambilan_alamat_pengambilan_id_foreign` (`alamat_pengambilan_id`),
  CONSTRAINT `rekap_pengambilan_alamat_pengambilan_id_foreign` FOREIGN KEY (`alamat_pengambilan_id`) REFERENCES `alamat_pengambilan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_calculations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `data_pencatatan_id` bigint unsigned NOT NULL,
  `year_month` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: 2024-01',
  `transaction_date` date NOT NULL,
  `volume_flow_meter` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `koreksi_meter` decimal(15,8) NOT NULL DEFAULT '1.00000000',
  `volume_sm3` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `harga_per_m3` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pricing_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Pricing yang digunakan saat perhitungan',
  `tekanan_keluar` decimal(10,3) DEFAULT NULL,
  `suhu` decimal(10,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL,
  `is_recalculated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_calculations_data_pencatatan_id_unique` (`data_pencatatan_id`),
  KEY `transaction_calculations_customer_id_year_month_index` (`customer_id`,`year_month`),
  KEY `transaction_calculations_customer_id_transaction_date_index` (`customer_id`,`transaction_date`),
  KEY `transaction_calculations_year_month_index` (`year_month`),
  KEY `idx_transaction_calc_date_amount` (`transaction_date`,`total_harga`),
  CONSTRAINT `transaction_calculations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_calculations_data_pencatatan_id_foreign` FOREIGN KEY (`data_pencatatan_id`) REFERENCES `data_pencatatan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_calculations_chk_1` CHECK (json_valid(`pricing_used`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_descriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_descriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `category` enum('kas','bank','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_descriptions_description_unique` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `u575891269_mpsfix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `u575891269_mpsfix` (
  `id` text,
  `nama_alamat` text,
  `created_at` text,
  `updated_at` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ukuran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ukuran` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_ukuran` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ukuran_nama_ukuran_unique` (`nama_ukuran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_deposit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_purchases` decimal(15,2) NOT NULL DEFAULT '0.00',
  `deposit_history` text COLLATE utf8mb4_unicode_ci,
  `monthly_balances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `pricing_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `balance_last_updated_at` timestamp NULL DEFAULT NULL,
  `use_realtime_calculation` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','superadmin','customer','fob','demo','keuangan','staff','mmbtu') COLLATE utf8mb4_unicode_ci DEFAULT 'customer',
  `no_kontrak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `nomor_tlpn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `harga_per_meter_kubik` decimal(10,2) DEFAULT '0.00' COMMENT 'Harga per meter kubik',
  `harga_per_mmbtu_usd` decimal(14,4) DEFAULT '0.0000',
  `pembagi_sm3_ke_mmbtu` decimal(14,6) DEFAULT '1.000000',
  `tekanan_keluar` decimal(10,3) DEFAULT '0.000' COMMENT 'Tekanan keluar dalam Bar',
  `suhu` decimal(10,2) DEFAULT '0.00' COMMENT 'Suhu dalam Celsius',
  `koreksi_meter` decimal(16,14) DEFAULT '1.00000000000000' COMMENT 'Faktor koreksi meter',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_users_realtime_flag` (`use_realtime_calculation`),
  CONSTRAINT `users_chk_1` CHECK (json_valid(`monthly_balances`)),
  CONSTRAINT `users_chk_2` CHECK (json_valid(`pricing_history`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- WARNING: can't read the INFORMATION_SCHEMA.libraries table. It's most probably an old server 8.0.33.
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_03_09_064403_create_data_pencatatans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_03_09_083436_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_03_10_080627_add_pricing_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_03_12_050632_deposit',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_03_17_070714_pricing_history',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_03_10_000000_add_staff_role_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_03_14_000001_add_mmbtu_role_to_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_03_14_000002_add_mmbtu_columns_to_proforma_invoices_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_03_14_000003_add_mmbtu_pricing_columns_to_users_table',6);
