-- SQL untuk menambahkan kolom baru ke tabel nomor_polisi
ALTER TABLE nomor_polisi
ADD COLUMN jenis VARCHAR(255) NULL AFTER keterangan,
ADD COLUMN ukuran VARCHAR(255) NULL AFTER jenis,
ADD COLUMN no_gtm VARCHAR(255) NULL AFTER ukuran,
ADD COLUMN status ENUM('milik', 'sewa', 'disewakan') NULL AFTER no_gtm,
ADD COLUMN iso ENUM('ISO - 11439', 'ISO - 11119') NULL AFTER status,
ADD COLUMN coi ENUM('sudah', 'belum') NULL AFTER iso;

-- Buat tabel untuk menyimpan daftar ukuran (opsional)
CREATE TABLE IF NOT EXISTS ukuran_truk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ukuran VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tambahkan beberapa ukuran awal (opsional)
INSERT IGNORE INTO ukuran_truk (ukuran) VALUES
('20 feet'),
('10 feet'),
('40 feet');
