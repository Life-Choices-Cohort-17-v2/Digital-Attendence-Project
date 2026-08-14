<?php
namespace Models;

class Settings
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllSettings(): array
    {
        $stmt = $this->pdo->query("SELECT id, `key`, `value` FROM settings");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}