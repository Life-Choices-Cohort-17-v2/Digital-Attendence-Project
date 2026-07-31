<?php

namespace App\Models;

use PDO;

/**
 * backend/src/models/QRCode.php
 *
 * Each row is one issued QR code. Content is deliberately just an opaque
 * random token (Option C from the plan: attendance_token=abc123xyz) — the
 * database is the source of truth for whether it's valid, not a signature,
 * which means a code can be revoked (lost badge, employee left) just by
 * updating a row.
 *
 * Takes a PDO connection via constructor injection rather than reaching
 * for a global — wire it to whatever backend/src/config/Database.php
 * actually exposes (e.g. Database::connect() or Database::getInstance()).
 */
final class QRCode
{
    private function __construct(
        private readonly PDO $pdo,
        public readonly int $id,
        public readonly string $employeeId,
        public readonly string $token,
        public readonly string $issuedAt,
        public readonly ?string $expiresAt,
        public readonly ?string $revokedAt
    ) {
    }

    /**
     * Ensures the qr_codes table exists
     */
    public static function ensureTableExists(PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS qr_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(50) NOT NULL,
            token VARCHAR(32) NOT NULL UNIQUE,
            issued_at DATETIME NOT NULL,
            expires_at DATETIME NULL,
            revoked_at DATETIME NULL,
            INDEX idx_token (token),
            INDEX idx_employee (employee_id)
        )";
        $pdo->exec($sql);
    }

    /**
     * Issues a new QR code for an employee and persists it.
     * $ttlSeconds = null means it never expires (badge-style).
     */
    public static function issueFor(PDO $pdo, string $employeeId, ?int $ttlSeconds = null): self
    {
        // Ensure table exists
        self::ensureTableExists($pdo);

        $token = bin2hex(random_bytes(16)); // 32 hex chars, opaque
        $issuedAt = date('Y-m-d H:i:s');
        $expiresAt = $ttlSeconds !== null ? date('Y-m-d H:i:s', time() + $ttlSeconds) : null;

        $stmt = $pdo->prepare(
            'INSERT INTO qr_codes (employee_id, token, issued_at, expires_at) VALUES (:employee_id, :token, :issued_at, :expires_at)'
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'token' => $token,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        $id = (int) $pdo->lastInsertId();

        return new self($pdo, $id, $employeeId, $token, $issuedAt, $expiresAt, null);
    }

    public static function findByToken(PDO $pdo, string $token): ?self
    {
        $stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new self(
            $pdo,
            (int) $row['id'],
            $row['employee_id'],
            $row['token'],
            $row['issued_at'],
            $row['expires_at'],
            $row['revoked_at']
        );
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && strtotime($this->expiresAt) < time();
    }

    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    public function revoke(): void
    {
        $stmt = $this->pdo->prepare('UPDATE qr_codes SET revoked_at = :now WHERE id = :id');
        $stmt->execute(['now' => date('Y-m-d H:i:s'), 'id' => $this->id]);
    }
}