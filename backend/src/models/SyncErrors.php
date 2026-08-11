$db = Database::getConnection();
$stmt = $db->query("
    SELECT se.id, se.error_message, se.attempts, se.resolved,
           ar.type, ar.timestamp, u.name
    FROM sync_errors se
    JOIN attendance_records ar ON se.attendance_id = ar.id
    JOIN users u ON ar.user_id = u.id
");
print_r($stmt->fetchAll());