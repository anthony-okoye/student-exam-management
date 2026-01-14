<?php
/**
 * Exam Controller
 * 
 * Handles exam taking operations including starting, saving answers, and submitting
 */

require_once BASE_PATH . '/models/Exam.php';
require_once BASE_PATH . '/models/ExamSession.php';
require_once BASE_PATH . '/models/Question.php';
require_once BASE_PATH . '/services/SecurityService.php';

class ExamController {
    
    /**
     * Start an exam and display the exam taking interface
     */
    public function startExam() {
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
        
        $studentId = $_SESSION['user_id'];
        
        // Check if student can take this exam (assignment and retake check)
        $canTake = Exam::canStudentTakeExam($examId, $studentId);
        if (!$canTake['allowed']) {
            $_SESSION['error'] = $canTake['reason'];
            header('Location: /student/dashboard');
            exit;
        }
        
        // Start or resume exam session
        $sessionId = ExamSession::start($examId, $studentId);
        
        if ($sessionId === false) {
            $_SESSION['error'] = 'Cannot start exam. You may have already completed it.';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Get exam details
        $exam = Exam::getById($examId);
        
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Get questions for this exam
        $questions = Exam::getAssignedQuestions($examId);
        
        // Get existing answers if resuming
        $existingAnswers = ExamSession::getAnswers($sessionId);
        $answersMap = [];
        foreach ($existingAnswers as $answer) {
            $answersMap[$answer['question_id']] = $answer;
        }
        
        // Get session details for timer
        $session = ExamSession::getById($sessionId);
        $remainingTime = ExamSession::getRemainingTime($sessionId);
        
        require BASE_PATH . '/views/student/exam-taking.php';
    }
    
    /**
     * Save answer via AJAX
     */
    public function saveAnswer() {
        header('Content-Type: application/json');
        
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        // Validate CSRF token from header
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!SecurityService::validateCSRFToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        
        $sessionId = intval($input['session_id'] ?? 0);
        $questionId = intval($input['question_id'] ?? 0);
        $answer = $input['answer'] ?? null;
        
        if ($sessionId <= 0 || $questionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        // Verify session belongs to this student
        $session = ExamSession::getById($sessionId);
        if (!$session || $session['student_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
            exit;
        }
        
        // Save answer (with timer validation)
        $result = ExamSession::saveAnswer($sessionId, $questionId, $answer);
        
        if ($result === 'time_expired') {
            // Time expired - exam was auto-submitted
            echo json_encode([
                'success' => false, 
                'time_expired' => true,
                'message' => 'Time expired. Your exam has been submitted automatically.',
                'redirect' => '/student/exam/results?session_id=' . $sessionId
            ]);
        } elseif ($result) {
            echo json_encode(['success' => true, 'message' => 'Answer saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save answer']);
        }
        exit;
    }
    
    /**
     * Submit exam via AJAX or form
     */
    public function submitExam() {
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
                isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            } else {
                header('Location: /login');
            }
            exit;
        }
        
        // Handle AJAX request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            
            // Validate CSRF token from header
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!SecurityService::validateCSRFToken($csrfToken)) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $sessionId = intval($input['session_id'] ?? 0);
            
            if ($sessionId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
                exit;
            }
            
            // Verify session belongs to this student
            $session = ExamSession::getById($sessionId);
            if (!$session || $session['student_id'] != $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Invalid session']);
                exit;
            }
            
            // Submit exam
            $result = ExamSession::submit($sessionId);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Exam submitted successfully',
                    'redirect' => '/student/exam/results?session_id=' . $sessionId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit exam']);
            }
            exit;
        }
        
        // Handle regular form submission
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /student/dashboard');
            exit;
        }
        
        $sessionId = intval($_POST['session_id'] ?? 0);
        
        if ($sessionId <= 0) {
            $_SESSION['error'] = 'Invalid session ID';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Verify session belongs to this student
        $session = ExamSession::getById($sessionId);
        if (!$session || $session['student_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'Invalid session';
            header('Location: /student/dashboard');
            exit;
        }
        
        // Submit exam
        $result = ExamSession::submit($sessionId);
        
        if ($result) {
            $_SESSION['success'] = 'Exam submitted successfully';
            header('Location: /student/exam/results?session_id=' . $sessionId);
        } else {
            $_SESSION['error'] = 'Failed to submit exam';
            header('Location: /student/dashboard');
        }
        exit;
    }
    
    /**
     * Get remaining time for a session via AJAX
     */
    public function getRemainingTime() {
        header('Content-Type: application/json');
        
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $sessionId = intval($_GET['session_id'] ?? 0);
        
        if ($sessionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
            exit;
        }
        
        // Verify session belongs to this student
        $session = ExamSession::getById($sessionId);
        if (!$session || $session['student_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
            exit;
        }
        
        $remainingTime = ExamSession::getRemainingTime($sessionId);
        
        if ($remainingTime !== false) {
            // Check if time has expired
            if ($remainingTime <= 0) {
                // Auto-submit the exam
                ExamSession::autoSubmit($sessionId);
                
                echo json_encode([
                    'success' => true, 
                    'remaining_time' => 0,
                    'time_expired' => true,
                    'message' => 'Time expired. Your exam has been submitted automatically.',
                    'redirect' => '/student/exam/results?session_id=' . $sessionId
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'remaining_time' => $remainingTime
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to get remaining time']);
        }
        exit;
    }
    
    /**
     * Get current exam state (for page refresh restoration)
     */
    public function getExamState() {
        header('Content-Type: application/json');
        
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $sessionId = intval($_GET['session_id'] ?? 0);
        
        if ($sessionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
            exit;
        }
        
        // Verify session belongs to this student
        $session = ExamSession::getById($sessionId);
        if (!$session || $session['student_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
            exit;
        }
        
        // Check if session is still in progress
        if ($session['status'] !== 'in_progress') {
            echo json_encode([
                'success' => false, 
                'message' => 'Exam session is not in progress',
                'redirect' => '/student/exam/results?session_id=' . $sessionId
            ]);
            exit;
        }
        
        // Get remaining time
        $remainingTime = ExamSession::getRemainingTime($sessionId);
        
        // Check if time has expired
        if ($remainingTime <= 0) {
            // Auto-submit the exam
            ExamSession::autoSubmit($sessionId);
            
            echo json_encode([
                'success' => false,
                'time_expired' => true,
                'message' => 'Time expired. Your exam has been submitted automatically.',
                'redirect' => '/student/exam/results?session_id=' . $sessionId
            ]);
            exit;
        }
        
        // Get exam details
        $exam = Exam::getById($session['exam_id']);
        
        // Get questions for this exam
        $questions = Exam::getAssignedQuestions($session['exam_id']);
        
        // Get existing answers
        $existingAnswers = ExamSession::getAnswers($sessionId);
        $answersMap = [];
        foreach ($existingAnswers as $answer) {
            $answersMap[$answer['question_id']] = [
                'answer_text' => $answer['answer_text'],
                'selected_options' => $answer['selected_options'] ? json_decode($answer['selected_options'], true) : []
            ];
        }
        
        // Return exam state
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'exam' => [
                'id' => $exam['id'],
                'title' => $exam['title'],
                'description' => $exam['description']
            ],
            'remaining_time' => $remainingTime,
            'answers' => $answersMap
        ]);
        exit;
    }
    
    /**
     * Log tab switch event (anti-cheating)
     */
    public function logTabSwitch() {
        header('Content-Type: application/json');
        
        // Check if user is student
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        // Validate CSRF token from header
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!SecurityService::validateCSRFToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
        
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = intval($input['session_id'] ?? 0);
        
        if ($sessionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
            exit;
        }
        
        // Verify session belongs to this student
        $session = ExamSession::getById($sessionId);
        if (!$session || $session['student_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Invalid session']);
            exit;
        }
        
        // Increment tab switch count
        $result = ExamSession::incrementTabSwitchCount($sessionId);
        
        if ($result !== false) {
            echo json_encode([
                'success' => true, 
                'count' => $result,
                'flagged' => $result >= 3
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to log tab switch']);
        }
        exit;
    }
}
