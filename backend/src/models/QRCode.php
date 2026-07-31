<?php
namespace Models;

use Config\Database;

class QRCode {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    public function create($data) {
        $sql = "INSERT INTO qr_codes (code, type, location, created_by, is_active)
                VALUES (:code, :type, :location, :created_by, :is_active)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM qr_codes WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function getActive($type) {
        $stmt = $this->db->prepare("SELECT * FROM qr_codes WHERE type = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$type]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        // implementation
    }
}