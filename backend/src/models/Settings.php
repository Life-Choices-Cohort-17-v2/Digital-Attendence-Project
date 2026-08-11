$db = Database::getConnection();
$stmt = $db->query("SELECT id, `key`, `value` FROM settings");
$settings = $stmt->fetchAll();
print_r($settings);