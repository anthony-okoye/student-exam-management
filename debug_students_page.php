<?php
/**
 * Debug script to check if students page has duplicate content
 */

// Start session
session_start();

// Set up paths
define('BASE_PATH', __DIR__);

// Include required files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/services/SecurityService.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Please login as admin first");
}

// Get students
$students = User::getByRole('student');

// Get exam history for each student
foreach ($students as &$student) {
    $student['exam_history'] = User::getExamHistory($student['id']);
}

echo "<h1>Debug: Students Data</h1>";
echo "<p>Total students found: " . count($students) . "</p>";
echo "<hr>";

echo "<h2>Students Array:</h2>";
echo "<pre>";
print_r($students);
echo "</pre>";

echo "<hr>";
echo "<h2>Check for duplicates:</h2>";

$usernames = array_column($students, 'username');
$uniqueUsernames = array_unique($usernames);

if (count($usernames) === count($uniqueUsernames)) {
    echo "<p style='color: green;'><strong>✓ NO DUPLICATES FOUND in database</strong></p>";
    echo "<p>All " . count($students) . " students are unique.</p>";
} else {
    echo "<p style='color: red;'><strong>✗ DUPLICATES FOUND in database!</strong></p>";
    $duplicates = array_diff_assoc($usernames, $uniqueUsernames);
    echo "<p>Duplicate usernames:</p>";
    echo "<pre>";
    print_r($duplicates);
    echo "</pre>";
}

echo "<hr>";
echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>If NO DUPLICATES are found above, the issue is in the HTML rendering</li>";
echo "<li>If DUPLICATES are found, there's a database issue</li>";
echo "<li>Check your browser's Developer Tools (F12) → Elements tab</li>";
echo "<li>Search for 'All Students' - it should appear only ONCE</li>";
echo "<li>If it appears twice, the view file is being included twice</li>";
echo "</ol>";
?>
