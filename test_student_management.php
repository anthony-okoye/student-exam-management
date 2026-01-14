<?php
/**
 * Test script for student management functionality
 * 
 * This script tests the student management features:
 * - Creating students
 * - Listing students
 * - Getting exam history
 * - Updating students
 * - Deleting students
 */

// Define base path
define('BASE_PATH', __DIR__);

// Include required files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/services/SecurityService.php';
require_once BASE_PATH . '/models/User.php';

echo "=== Student Management Functionality Test ===\n\n";

// Test 1: Create a test student
echo "Test 1: Creating a test student...\n";
$testUsername = 'test_student_' . time();
$testEmail = 'test' . time() . '@example.com';
$testPassword = 'testpass123';

$studentId = User::create($testUsername, $testEmail, $testPassword, 'student');

if ($studentId) {
    echo "✓ Student created successfully with ID: $studentId\n";
} else {
    echo "✗ Failed to create student\n";
    exit(1);
}

// Test 2: Get all students
echo "\nTest 2: Getting all students...\n";
$allStudents = User::getByRole('student');
echo "✓ Found " . count($allStudents) . " student(s)\n";

// Test 3: Get student by ID
echo "\nTest 3: Getting student by ID...\n";
$student = User::getById($studentId);
if ($student && $student['username'] === $testUsername) {
    echo "✓ Student retrieved successfully\n";
    echo "  Username: " . $student['username'] . "\n";
    echo "  Email: " . $student['email'] . "\n";
    echo "  Role: " . $student['role'] . "\n";
} else {
    echo "✗ Failed to retrieve student\n";
}

// Test 4: Get exam history (should be empty for new student)
echo "\nTest 4: Getting exam history...\n";
$examHistory = User::getExamHistory($studentId);
echo "✓ Exam history retrieved: " . count($examHistory) . " exam(s)\n";

// Test 5: Update student
echo "\nTest 5: Updating student...\n";
$newUsername = $testUsername . '_updated';
$newEmail = 'updated_' . $testEmail;
$updateResult = User::update($studentId, $newUsername, $newEmail);

if ($updateResult) {
    echo "✓ Student updated successfully\n";
    
    // Verify update
    $updatedStudent = User::getById($studentId);
    if ($updatedStudent['username'] === $newUsername && $updatedStudent['email'] === $newEmail) {
        echo "✓ Update verified\n";
    } else {
        echo "✗ Update verification failed\n";
    }
} else {
    echo "✗ Failed to update student\n";
}

// Test 6: Update student with new password
echo "\nTest 6: Updating student password...\n";
$newPassword = 'newpass456';
$updateResult = User::update($studentId, $newUsername, $newEmail, $newPassword);

if ($updateResult) {
    echo "✓ Student password updated successfully\n";
    
    // Verify password was hashed
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
    $stmt->execute(['id' => $studentId]);
    $result = $stmt->fetch();
    
    if ($result && SecurityService::verifyPassword($newPassword, $result['password_hash'])) {
        echo "✓ Password hash verified\n";
    } else {
        echo "✗ Password verification failed\n";
    }
} else {
    echo "✗ Failed to update student password\n";
}

// Test 7: Delete student
echo "\nTest 7: Deleting student...\n";
$deleteResult = User::delete($studentId);

if ($deleteResult) {
    echo "✓ Student deleted successfully\n";
    
    // Verify deletion
    $deletedStudent = User::getById($studentId);
    if (!$deletedStudent) {
        echo "✓ Deletion verified\n";
    } else {
        echo "✗ Deletion verification failed\n";
    }
} else {
    echo "✗ Failed to delete student\n";
}

echo "\n=== All tests completed ===\n";
