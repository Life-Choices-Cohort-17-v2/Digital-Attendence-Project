$db = Database::getConnection();
$stmt = $db->query("SELECT employee_id, name, role FROM users");
$users = $stmt->fetchAll();
print_r($users);