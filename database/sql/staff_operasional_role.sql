-- ============================================================================
-- Role baru: staff_operasional
-- Akses: HANYA MELIHAT (read-only) menu Lembur Operator GTM & Pencatatan Data Customer.
-- Tidak bisa input / edit / hapus.
--
-- Jalankan SQL ini di database hosting (sekali saja), setara dengan migration
-- database/migrations/2026_09_07_000001_add_staff_operasional_role_to_users_table.php
-- ============================================================================

ALTER TABLE users
    MODIFY COLUMN role ENUM(
        'admin',
        'superadmin',
        'customer',
        'fob',
        'demo',
        'keuangan',
        'staff',
        'mmbtu',
        'operator',
        'staff_operasional'
    ) DEFAULT 'customer';
