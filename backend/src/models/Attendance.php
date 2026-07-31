<?php
namespace Models;

use Config\Database;

class Attendance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    public function create($data) {
        $sql = "INSERT INTO attendance_records (user_id, type, timestamp, location, device, qr_code, sync_status)
                VALUES (:user_id, :type, :timestamp, :location, :device, :qr_code, :sync_status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM attendance_records WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        // implementation
    }

    public function getPendingSync() {
        $stmt = $this->db->prepare("SELECT * FROM attendance_records WHERE sync_status = 'pending'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markSynced($id, $rowId = null) {
        $sql = "UPDATE attendance_records SET sync_status = 'synced'";
        if ($rowId) $sql .= ", google_sheet_row_id = :rowId";
        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($rowId) $params['rowId'] = $rowId;
        return $stmt->execute($params);
    }

    public function getByUser($userId, $limit = null) {
        $sql = "SELECT * FROM attendance_records WHERE user_id = ? ORDER BY timestamp DESC";
        if ($limit) $sql .= " LIMIT $limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getTodayEvents() {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM attendance_records WHERE DATE(timestamp) = ?");
        $stmt->execute([$today]);
        return $stmt->fetch()['count'];
    }

    // get recent events, etc.
}