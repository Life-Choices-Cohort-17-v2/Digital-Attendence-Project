<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getConnection();
$stmt = $db->query("SELECT employee_id, name, role, last_qr_scan FROM users");
$users = $stmt->fetchAll();
print_r($users);
?>