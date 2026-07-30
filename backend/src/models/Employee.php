<?php
namespace Models;

class Employee 
{
    private ?\PDO $pdo;

    private array $mockEmployees = [
        'EMP-001' => ['employee_id' => 'EMP-001', 'name' => 'Sarah Mthembu', 'status' => 'active', 'qr_code' => 'QR-SARAH-001'],
        'EMP-002' => ['employee_id' => 'EMP-002', 'name' => 'Demo Staff', 'status' => 'inactive', 'qr_code' => 'QR-DEMO-002']
    ];

    public function __construct(?\PDO $pdo = null) 
    {
        $this->pdo = $pdo;
    }

    public function findById(string $employeeId): ?array 
    {
        return $this->mockEmployees[$employeeId] ?? null;
    }

    public function findByQrCode(string $qrCode): ?array 
    {
        foreach ($this->mockEmployees as $employee) {
            if ($employee['qr_code'] === $qrCode) {
                return $employee;
            }
        }
        return null;
    }
}