<?php
/**
 * Student Controller
 * 
 * Handles student operations for viewing exams and taking assessments
 */

require_once BASE_PATH . '/models/Exam.php';

class StudentController {
    
    /**
     * Display student dashboard with all available exams
     * Now filters by assigned exams only
     */
    public function dashboard() {
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: /login');
            exit;
        }
        
        $studentId = $_SESSION['user_id'];
        
        // Get exams assigned to this student
        $exams = Exam::getAssignedToStudent($studentId);
        
        // Get exam sessions for this student to determine status
        $examStatuses = self::getExamStatusesForStudent($studentId);
        
        require BASE_PATH . '/views/student/dashboard.php';
    }
    
    /**
     * Display exam instructions page
     */
    public function examInstructions() {
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: /login');
            exit;
        }
        
        $examId = intval($_GET['id'] ?? 0);
        
        if ($examId <= 0) {
            $_SESSION['error'] = 'Invalid exam ID';
            header('Location: /student/dashboard');
            exit;
        }
        
        $exam = Exam::getById($examId);
        
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found';
            header('Location: /student/dashboard');
            exit;
        }
        
        $studentId = $_SESSION['user_id'];
        
        // Check if student can take this exam
        $canTake = Exam::canStudentTakeExam($examId, $studentId);
        if (!$canTake['allowed']) {
            $_SESSION['error'] = $canTake['reason'];
            header('Location: /student/dashboard');
            exit;
        }
        
        $examStatus = self::getExamStatusForStudent($studentId, $examId);
        
        require BASE_PATH . '/views/student/exam-instructions.php';
    }
    
    /**
     * Get exam statuses for a student
     * Returns array with exam_id as key and status info as value
     * 
     * @param int $studentId Student ID
     * @return array Exam statuses
     */
    private static function getExamStatusesForStudent($studentId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT id, exam_id, status, score, percentage 
                 FROM exam_sessions 
                 WHERE student_id = :student_id"
            );
            $stmt->execute(['student_id' => $studentId]);
            $sessions = $stmt->fetchAll();
            
            $statuses = [];
            foreach ($sessions as $session) {
                $statuses[$session['exam_id']] = [
                    'session_id' => $session['id'] ?? null,
                    'status' => $session['status'],
                    'score' => $session['score'],
                    'percentage' => $session['percentage']
                ];
            }
            
            return $statuses;
        } catch (PDOException $e) {
            error_log("Error fetching exam statuses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get exam status for a specific student and exam
     * 
     * @param int $studentId Student ID
     * @param int $examId Exam ID
     * @return array|null Status info or null if not started
     */
    private static function getExamStatusForStudent($studentId, $examId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT id, status, score, percentage, start_time, end_time 
                 FROM exam_sessions 
                 WHERE student_id = :student_id AND exam_id = :exam_id
                 ORDER BY created_at DESC
                 LIMIT 1"
            );
            $stmt->execute([
                'student_id' => $studentId,
                'exam_id' => $examId
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching exam status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Display exam results page
     */
    public function results() {
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: /login');
            exit;
        }
        
        $sessionId = intval($_GET['session_id'] ?? 0);
        
        if ($sessionId <= 0) {
            $_SESSION['error'] = 'Invalid session ID';
            header('Location: /student/dashboard');
            exit;
        }
        
        $studentId = $_SESSION['user_id'];
        
        // Get session details
        require_once BASE_PATH . '/models/ExamSession.php';
        $session = ExamSession::getById($sessionId);
        
        if (!$session) {
            $_SESSION['error'] = 'Session not found';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Verify session belongs to this student
        if ($session['student_id'] != $studentId) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Verify session is completed
        if ($session['status'] !== 'completed' && $session['status'] !== 'auto_submitted') {
            $_SESSION['error'] = 'Exam not yet completed';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Get all answers with question details
        $pdo = getDBConnection();
        $stmt = $pdo->prepare(
            "SELECT a.*, q.type, q.content, q.marks,
                    a.is_correct, a.marks_awarded
             FROM answers a
             JOIN questions q ON a.question_id = q.id
             WHERE a.session_id = :session_id
             ORDER BY q.id ASC"
        );
        $stmt->execute(['session_id' => $sessionId]);
        $answers = $stmt->fetchAll();
        
        // Get correct answers and options for each question
        foreach ($answers as &$answer) {
            // Get all options for this question
            $optionsStmt = $pdo->prepare(
                "SELECT id, option_text, is_correct 
                 FROM question_options 
                 WHERE question_id = :question_id 
                 ORDER BY option_order ASC"
            );
            $optionsStmt->execute(['question_id' => $answer['question_id']]);
            $answer['options'] = $optionsStmt->fetchAll();
            
            // Parse selected options if JSON
            if ($answer['selected_options']) {
                $answer['selected_options_array'] = json_decode($answer['selected_options'], true);
            } else {
                $answer['selected_options_array'] = [];
            }
        }
        
        // Calculate pass/fail (assuming 50% is passing)
        $passingPercentage = 50;
        $isPassed = $session['percentage'] >= $passingPercentage;
        
        require BASE_PATH . '/views/student/results.php';
    }
}
