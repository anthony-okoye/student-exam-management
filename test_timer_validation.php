<?php
/**
 * Test Server-Side Timer Validation
 * 
 * This script tests the server-side timer validation functionality
 */

// Start session
session_start();

// Define base path
define('BASE_PATH', __DIR__);

// Include required files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/ExamSession.php';
require_once BASE_PATH . '/models/Exam.php';
require_once BASE_PATH . '/models/User.php';

echo "<h1>Server-Side Timer Validation Test</h1>\n";
echo "<pre>\n";

try {
    $pdo = getDBConnection();
    
    // Test 1: Check if getRemainingTime works correctly
    echo "Test 1: Testing getRemainingTime method\n";
    echo "========================================\n";
    
    // Get an active session (if any)
    $stmt = $pdo->prepare("SELECT id FROM exam_sessions WHERE status = 'in_progress' LIMIT 1");
    $stmt->execute();
    $activeSession = $stmt->fetch();
    
    if ($activeSession) {
        $sessionId = $activeSession['id'];
        $remainingTime = ExamSession::getRemainingTime($sessionId);
        
        if ($remainingTime !== false) {
            echo "✓ getRemainingTime returned: " . $remainingTime . " seconds\n";
            echo "  (" . floor($remainingTime / 60) . " minutes " . ($remainingTime % 60) . " seconds)\n";
        } else {
            echo "✗ getRemainingTime failed\n";
        }
    } else {
        echo "⚠ No active sessions found to test\n";
    }
    
    echo "\n";
    
    // Test 2: Create a test session with expired time
    echo "Test 2: Testing auto-submit on expired timer\n";
    echo "=============================================\n";
    
    // Get a test student and exam
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'student' LIMIT 1");
    $stmt->execute();
    $student = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT id FROM exams LIMIT 1");
    $stmt->execute();
    $exam = $stmt->fetch();
    
    if ($student && $exam) {
        // Create a test session with start time in the past (2 hours ago)
        $stmt = $pdo->prepare(
            "INSERT INTO exam_sessions (exam_id, student_id, start_time, status) 
             VALUES (:exam_id, :student_id, DATE_SUB(NOW(), INTERVAL 2 HOUR), 'in_progress')"
        );
        $stmt->execute([
            'exam_id' => $exam['id'],
            'student_id' => $student['id']
        ]);
        $testSessionId = $pdo->lastInsertId();
        
        echo "✓ Created test session with ID: $testSessionId\n";
        echo "  Start time: 2 hours ago\n";
        
        // Check remaining time (should be negative/zero)
        $remainingTime = ExamSession::getRemainingTime($testSessionId);
        echo "  Remaining time: " . $remainingTime . " seconds\n";
        
        if ($remainingTime <= 0) {
            echo "✓ Timer correctly detected as expired\n";
            
            // Test auto-submit
            $result = ExamSession::autoSubmit($testSessionId);
            
            if ($result) {
                echo "✓ Auto-submit successful\n";
                
                // Verify status changed
                $stmt = $pdo->prepare("SELECT status FROM exam_sessions WHERE id = :id");
                $stmt->execute(['id' => $testSessionId]);
                $session = $stmt->fetch();
                
                if ($session['status'] === 'auto_submitted') {
                    echo "✓ Session status correctly set to 'auto_submitted'\n";
                } else {
                    echo "✗ Session status is '" . $session['status'] . "' (expected 'auto_submitted')\n";
                }
            } else {
                echo "✗ Auto-submit failed\n";
            }
        } else {
            echo "✗ Timer not detected as expired\n";
        }
        
        // Clean up test session
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE id = :id");
        $stmt->execute(['id' => $testSessionId]);
        echo "✓ Test session cleaned up\n";
    } else {
        echo "⚠ No student or exam found to create test session\n";
    }
    
    echo "\n";
    
    // Test 3: Test saveAnswer with timer validation
    echo "Test 3: Testing saveAnswer with timer validation\n";
    echo "=================================================\n";
    
    if ($student && $exam) {
        // Create another test session with expired time
        $stmt = $pdo->prepare(
            "INSERT INTO exam_sessions (exam_id, student_id, start_time, status) 
             VALUES (:exam_id, :student_id, DATE_SUB(NOW(), INTERVAL 3 HOUR), 'in_progress')"
        );
        $stmt->execute([
            'exam_id' => $exam['id'],
            'student_id' => $student['id']
        ]);
        $testSessionId = $pdo->lastInsertId();
        
        echo "✓ Created test session with ID: $testSessionId (expired)\n";
        
        // Try to save an answer (should trigger auto-submit)
        $result = ExamSession::saveAnswer($testSessionId, 1, 'test answer');
        
        if ($result === 'time_expired') {
            echo "✓ saveAnswer correctly detected time expiration\n";
            
            // Verify session was auto-submitted
            $stmt = $pdo->prepare("SELECT status FROM exam_sessions WHERE id = :id");
            $stmt->execute(['id' => $testSessionId]);
            $session = $stmt->fetch();
            
            if ($session['status'] === 'auto_submitted') {
                echo "✓ Session was auto-submitted by saveAnswer\n";
            } else {
                echo "✗ Session status is '" . $session['status'] . "' (expected 'auto_submitted')\n";
            }
        } else {
            echo "✗ saveAnswer did not detect time expiration (returned: " . var_export($result, true) . ")\n";
        }
        
        // Clean up test session
        $stmt = $pdo->prepare("DELETE FROM exam_sessions WHERE id = :id");
        $stmt->execute(['id' => $testSessionId]);
        echo "✓ Test session cleaned up\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "All tests completed!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>
