<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Sets up the testing environment
 */

// Define BASE_PATH constant
define('BASE_PATH', dirname(__DIR__));

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from phpunit.xml
$_ENV['DB_CONNECTION'] = getenv('DB_CONNECTION') ?: 'mysql';
$_ENV['DB_HOST'] = getenv('DB_HOST') ?: 'localhost';
$_ENV['DB_PORT'] = getenv('DB_PORT') ?: '3307';
$_ENV['DB_NAME'] = getenv('DB_NAME') ?: 'hope_nurse_test';
$_ENV['DB_USER'] = getenv('DB_USER') ?: 'root';
$_ENV['DB_PASS'] = getenv('DB_PASS') ?: 'admin';
$_ENV['DB_CHARSET'] = getenv('DB_CHARSET') ?: 'utf8';

// Require database configuration
require_once BASE_PATH . '/config/database.php';

// Require models
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/models/Question.php';
require_once BASE_PATH . '/models/Exam.php';
require_once BASE_PATH . '/models/ExamSession.php';

// Require services
require_once BASE_PATH . '/services/SecurityService.php';

/**
 * Helper function to clean up test database
 */
function cleanupTestDatabase() {
    try {
        $pdo = getDBConnection();
        
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Truncate all tables
        $tables = ['answers', 'exam_sessions', 'exam_assignments', 'exam_questions', 
                   'question_options', 'questions', 'exams', 'users'];
        
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        return true;
    } catch (PDOException $e) {
        error_log("Error cleaning up test database: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to create test admin user
 */
function createTestAdmin() {
    return User::create('test_admin', 'admin@test.com', 'admin123', 'admin');
}

/**
 * Helper function to create test student user
 */
function createTestStudent($username = 'test_student', $email = 'student@test.com') {
    return User::create($username, $email, 'student123', 'student');
}
