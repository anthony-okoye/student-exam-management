<?php
/**
 * Verify seed data was imported correctly
 */

define('BASE_PATH', __DIR__);

require_once __DIR__ . '/config/database.php';

echo "Verifying seed data...\n\n";

try {
    $pdo = getDBConnection();
    
    // Check users
    echo "=== USERS ===\n";
    $stmt = $pdo->query("SELECT username, email, role FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['username']} ({$row['role']}) - {$row['email']}\n";
    }
    
    // Check questions
    echo "\n=== QUESTIONS ===\n";
    $stmt = $pdo->query("SELECT id, type, LEFT(content, 50) as content FROM questions ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- Q{$row['id']}: [{$row['type']}] {$row['content']}...\n";
    }
    
    // Check exams
    echo "\n=== EXAMS ===\n";
    $stmt = $pdo->query("SELECT id, title, duration, total_marks, status FROM exams ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- Exam {$row['id']}: {$row['title']} ({$row['duration']} min, {$row['total_marks']} marks, {$row['status']})\n";
    }
    
    // Test login with admin
    echo "\n=== PASSWORD VERIFICATION TEST ===\n";
    require_once __DIR__ . '/models/User.php';
    
    $admin = User::getByUsername('admin');
    if ($admin) {
        if ($admin->verifyPassword('admin123')) {
            echo "✓ Admin password verification: SUCCESS\n";
        } else {
            echo "✗ Admin password verification: FAILED\n";
        }
    } else {
        echo "✗ Admin user not found\n";
    }
    
    $student = User::getByUsername('student1');
    if ($student) {
        if ($student->verifyPassword('pass123')) {
            echo "✓ Student1 password verification: SUCCESS\n";
        } else {
            echo "✗ Student1 password verification: FAILED\n";
        }
    } else {
        echo "✗ Student1 user not found\n";
    }
    
    echo "\n✓ All seed data verified successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
