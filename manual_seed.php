<?php
/**
 * Manual Seed Script
 * Run this if the automatic seeding didn't work
 */

require 'config/database.php';

try {
    echo "Connecting to database...\n";
    $pdo = getDBConnection();
    echo "✓ Connected successfully\n\n";
    
    // Clear existing data (in correct order due to foreign keys)
    echo "Clearing existing data...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE answers");
    $pdo->exec("TRUNCATE TABLE exam_sessions");
    $pdo->exec("TRUNCATE TABLE exam_questions");
    $pdo->exec("TRUNCATE TABLE exams");
    $pdo->exec("TRUNCATE TABLE question_options");
    $pdo->exec("TRUNCATE TABLE questions");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "  ✓ Existing data cleared\n\n";
    
    // Start transaction AFTER truncate
    $pdo->beginTransaction();
    
    echo "Inserting users...\n";
    
    // Insert admin
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@example.com', 'admin123', 'admin']);
    echo "  ✓ Admin user created\n";
    
    // Insert students
    $stmt->execute(['student1', 'student1@example.com', 'pass123', 'student']);
    $stmt->execute(['student2', 'student2@example.com', 'pass123', 'student']);
    echo "  ✓ Student users created\n\n";
    
    echo "Inserting questions...\n";
    
    // Multiple choice questions
    $stmt = $pdo->prepare("INSERT INTO questions (type, content, marks, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute(['multiple_choice', 'What is 2 + 2?', 1, 1]);
    $stmt->execute(['multiple_choice', 'Which programming language is this system built with?', 1, 1]);
    $stmt->execute(['multiple_choice', 'What does MVC stand for?', 1, 1]);
    
    // True/False questions
    $stmt->execute(['true_false', 'PHP is a server-side programming language.', 1, 1]);
    $stmt->execute(['true_false', 'MySQL is a NoSQL database.', 1, 1]);
    $stmt->execute(['true_false', 'Bootstrap is a CSS framework.', 1, 1]);
    
    // Fill in blank questions
    $stmt->execute(['fill_blank', 'The capital of France is ____.', 1, 1]);
    $stmt->execute(['fill_blank', 'HTML stands for HyperText ____ Language.', 1, 1]);
    $stmt->execute(['fill_blank', 'The default port for HTTP is ____.', 1, 1]);
    
    echo "  ✓ 9 questions created\n\n";
    
    echo "Inserting question options...\n";
    
    $stmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
    
    // Q1 options
    $stmt->execute([1, '3', 0, 1]);
    $stmt->execute([1, '4', 1, 2]);
    $stmt->execute([1, '5', 0, 3]);
    $stmt->execute([1, '6', 0, 4]);
    
    // Q2 options
    $stmt->execute([2, 'Python', 0, 1]);
    $stmt->execute([2, 'PHP', 1, 2]);
    $stmt->execute([2, 'Java', 0, 3]);
    $stmt->execute([2, 'Ruby', 0, 4]);
    
    // Q3 options
    $stmt->execute([3, 'Model View Controller', 1, 1]);
    $stmt->execute([3, 'Multiple View Container', 0, 2]);
    $stmt->execute([3, 'Main Visual Component', 0, 3]);
    $stmt->execute([3, 'Model Verification Code', 0, 4]);
    
    // Q4 options (True/False)
    $stmt->execute([4, 'True', 1, 1]);
    $stmt->execute([4, 'False', 0, 2]);
    
    // Q5 options (True/False)
    $stmt->execute([5, 'True', 0, 1]);
    $stmt->execute([5, 'False', 1, 2]);
    
    // Q6 options (True/False)
    $stmt->execute([6, 'True', 1, 1]);
    $stmt->execute([6, 'False', 0, 2]);
    
    // Q7-9 options (Fill in blank - correct answers)
    $stmt->execute([7, 'Paris', 1, 1]);
    $stmt->execute([8, 'Markup', 1, 1]);
    $stmt->execute([9, '80', 1, 1]);
    
    echo "  ✓ Question options created\n\n";
    
    echo "Inserting exams...\n";
    
    $stmt = $pdo->prepare("INSERT INTO exams (title, description, duration, total_marks, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Sample Programming Quiz', 'A quick quiz to test basic programming knowledge', 6, 3, 'in_progress', 1]);
    $stmt->execute(['Web Development Basics', 'Test your knowledge of web development fundamentals', 12, 6, 'in_progress', 1]);
    $stmt->execute(['Complete Assessment', 'Comprehensive test covering all question types', 18, 9, 'in_progress', 1]);
    
    echo "  ✓ 3 exams created\n\n";
    
    echo "Assigning questions to exams...\n";
    
    $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (?, ?, ?)");
    
    // Exam 1: Sample Programming Quiz
    $stmt->execute([1, 1, 1]);
    $stmt->execute([1, 4, 2]);
    $stmt->execute([1, 7, 3]);
    
    // Exam 2: Web Development Basics
    $stmt->execute([2, 2, 1]);
    $stmt->execute([2, 3, 2]);
    $stmt->execute([2, 5, 3]);
    $stmt->execute([2, 6, 4]);
    $stmt->execute([2, 8, 5]);
    $stmt->execute([2, 9, 6]);
    
    // Exam 3: Complete Assessment
    for ($i = 1; $i <= 9; $i++) {
        $stmt->execute([3, $i, $i]);
    }
    
    echo "  ✓ Questions assigned to exams\n\n";
    
    // Commit transaction
    $pdo->commit();
    
    echo "═══════════════════════════════════════\n";
    echo "✓ Seeding completed successfully!\n";
    echo "═══════════════════════════════════════\n\n";
    
    // Show counts
    $result = $pdo->query('SELECT COUNT(*) FROM users');
    echo "Users: " . $result->fetchColumn() . "\n";
    
    $result = $pdo->query('SELECT COUNT(*) FROM questions');
    echo "Questions: " . $result->fetchColumn() . "\n";
    
    $result = $pdo->query('SELECT COUNT(*) FROM exams');
    echo "Exams: " . $result->fetchColumn() . "\n";
    
    echo "\nYou can now start the server:\n";
    echo "  cd public && php -S localhost:8000\n\n";
    echo "Login with:\n";
    echo "  Admin: admin / admin123\n";
    echo "  Student: student1 / pass123\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check your internet connection\n";
    echo "2. Verify Supabase credentials in .env\n";
    echo "3. Check if your IP is whitelisted in Supabase\n";
    exit(1);
}
