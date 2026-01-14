<?php
/**
 * Test script for exam assignment functionality
 */

// Define base path
define('BASE_PATH', __DIR__);

// Include database configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Exam.php';
require_once BASE_PATH . '/models/User.php';

echo "=== Testing Exam Assignment Functionality ===\n\n";

try {
    // Get a test exam
    $exams = Exam::getAll();
    if (empty($exams)) {
        echo "❌ No exams found. Please create an exam first.\n";
        exit(1);
    }
    $testExam = $exams[0];
    echo "✓ Found test exam: {$testExam['title']} (ID: {$testExam['id']})\n";
    
    // Get test students
    $students = User::getByRole('student');
    if (empty($students)) {
        echo "❌ No students found. Please create students first.\n";
        exit(1);
    }
    echo "✓ Found " . count($students) . " students\n";
    
    // Test 1: Assign exam to students
    echo "\n--- Test 1: Assign exam to students ---\n";
    $studentIds = array_slice(array_column($students, 'id'), 0, 2); // Get first 2 students
    $result = Exam::assignToStudents($testExam['id'], $studentIds, false);
    if ($result) {
        echo "✓ Successfully assigned exam to " . count($studentIds) . " students\n";
    } else {
        echo "❌ Failed to assign exam to students\n";
    }
    
    // Test 2: Get assigned students
    echo "\n--- Test 2: Get assigned students ---\n";
    $assignedStudents = Exam::getAssignedStudents($testExam['id']);
    echo "✓ Retrieved " . count($assignedStudents) . " assigned students\n";
    foreach ($assignedStudents as $student) {
        echo "  - {$student['username']} (ID: {$student['student_id']})\n";
    }
    
    // Test 3: Check if student is assigned
    echo "\n--- Test 3: Check if student is assigned ---\n";
    $testStudentId = $studentIds[0];
    $assignment = Exam::isAssignedToStudent($testExam['id'], $testStudentId);
    if ($assignment) {
        echo "✓ Student {$testStudentId} is assigned to exam\n";
        echo "  - Retake allowed: " . ($assignment['retake_allowed'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Student {$testStudentId} is not assigned to exam\n";
    }
    
    // Test 4: Get exams assigned to student
    echo "\n--- Test 4: Get exams assigned to student ---\n";
    $assignedExams = Exam::getAssignedToStudent($testStudentId);
    echo "✓ Student {$testStudentId} has " . count($assignedExams) . " assigned exams\n";
    foreach ($assignedExams as $exam) {
        echo "  - {$exam['title']} (ID: {$exam['id']})\n";
    }
    
    // Test 5: Check if student can take exam
    echo "\n--- Test 5: Check if student can take exam ---\n";
    $canTake = Exam::canStudentTakeExam($testExam['id'], $testStudentId);
    echo "✓ Can take exam: " . ($canTake['allowed'] ? 'Yes' : 'No') . "\n";
    echo "  - Reason: {$canTake['reason']}\n";
    
    // Test 6: Test retake prevention
    echo "\n--- Test 6: Test retake prevention ---\n";
    // Simulate a completed exam session
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "INSERT INTO exam_sessions (exam_id, student_id, start_time, end_time, status, score, percentage) 
         VALUES (:exam_id, :student_id, NOW(), NOW(), 'completed', 80, 80.00)"
    );
    $stmt->execute([
        'exam_id' => $testExam['id'],
        'student_id' => $testStudentId
    ]);
    echo "✓ Created completed exam session\n";
    
    // Check if student can retake
    $canTake = Exam::canStudentTakeExam($testExam['id'], $testStudentId);
    echo "✓ Can retake exam: " . ($canTake['allowed'] ? 'Yes' : 'No') . "\n";
    echo "  - Reason: {$canTake['reason']}\n";
    
    // Test 7: Test with retake allowed
    echo "\n--- Test 7: Test with retake allowed ---\n";
    $result = Exam::assignToStudents($testExam['id'], $studentIds, true); // Enable retakes
    if ($result) {
        echo "✓ Updated assignment to allow retakes\n";
    }
    
    $canTake = Exam::canStudentTakeExam($testExam['id'], $testStudentId);
    echo "✓ Can retake exam: " . ($canTake['allowed'] ? 'Yes' : 'No') . "\n";
    echo "  - Reason: {$canTake['reason']}\n";
    
    // Cleanup
    echo "\n--- Cleanup ---\n";
    $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE exam_id = :exam_id AND student_id = :student_id");
    $stmt->execute([
        'exam_id' => $testExam['id'],
        'student_id' => $testStudentId
    ]);
    echo "✓ Cleaned up test data\n";
    
    echo "\n=== All tests completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
