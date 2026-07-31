<?php
namespace Models;

use Config\Database;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getOnsiteStaff() {
        $today = date('Y-m-d');
        $sql = "SELECT DISTINCT u.* FROM users u
                JOIN attendance_records a ON u.id = a.user_id
                WHERE DATE(a.timestamp) = ? AND a.type = 'sign_in'
                AND NOT EXISTS (
                    SELECT 1 FROM attendance_records a2
                    WHERE a2.user_id = u.id AND DATE(a2.timestamp) = ?
                    AND a2.type = 'sign_out' AND a2.timestamp > a.timestamp
                )
                AND u.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$today, $today]);
        return $stmt->fetchAll();
    }

    // Other methods: create, update, delete, etc.
}