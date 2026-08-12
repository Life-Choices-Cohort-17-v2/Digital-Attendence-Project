<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getConnection();
$stmt = $db->query("SELECT id, user_id, type, timestamp, sync_status FROM attendance_records ORDER BY id");
$records = $stmt->fetchAll();
print_r($records);
?>