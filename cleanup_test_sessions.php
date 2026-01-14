<?php
define('BASE_PATH', __DIR__);
require_once 'config/database.php';

$pdo = getDBConnection();

// Clean up any test sessions
$stmt = $pdo->query("DELETE FROM exam_sessions WHERE exam_id = 4 AND student_id IN (2, 3)");
echo "Cleaned up " . $stmt->rowCount() . " test sessions\n";
