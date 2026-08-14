<?php
namespace Models;

class Employee 
{
    private array $mockEmployees = [
        'S001' => [
            'id' => 1,
            'employee_id' => 'S001', 
            'name' => 'Sarah Mthembu', 
            'status' => 'active', 
            'qr_code' => 'QR_IN_SARAH_001',
            'revoked_at' => null,
            'expires_at' => '2030-01-01 00:00:00'
        ],
        'S002' => [
            'id' => 2,
            'employee_id' => 'S002', 
            'name' => 'John Doe', 
            'status' => 'active', 
            'qr_code' => 'QR_JOHN_REVOKED',
            'revoked_at' => '2026-08-01 10:00:00',
            'expires_at' => '2030-01-01 00:00:00'
        ],
        'ADMIN001' => [
            'id' => 3,
            'employee_id' => 'ADMIN001', 
            'name' => 'Admin User', 
            'status' => 'active', 
            'qr_code' => 'QR_OUT_ADMIN_EXP',
            'revoked_at' => null,
            'expires_at' => '2025-01-01 00:00:00'
        ]
    ];

    public function findByQrCode(string $qrToken): ?array 
    {
        foreach ($this->mockEmployees as $emp) {
            if ($emp['qr_code'] === $qrToken) {
                return $emp;
            }
        }
        return null;
    }

    public function findById(string $employeeId): ?array 
    {
        return $this->mockEmployees[$employeeId] ?? null;
    }
}