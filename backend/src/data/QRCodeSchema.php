<?php

/**
 * backend/src/data/QRCodeSchema.php
 *
 * Schema for the table App\Models\QRCode reads/writes. Not an auto-run
 * migration (this repo doesn't show a migration runner) — run the SQL
 * manually against your DB, or hook it into one if you add a runner later.
 *
 * MySQL/MariaDB shown. On Postgres: AUTO_INCREMENT -> SERIAL / GENERATED
 * ALWAYS AS IDENTITY, DATETIME -> TIMESTAMP.
 */
return [
    'create_qr_codes_table' => <<<SQL
        CREATE TABLE IF NOT EXISTS qr_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(64) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            issued_at DATETIME NOT NULL,
            expires_at DATETIME NULL,
            revoked_at DATETIME NULL,
            INDEX idx_qr_codes_employee_id (employee_id),
            INDEX idx_qr_codes_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL,
];