<?php
/**
 * Admin Controller
 * 
 * Handles admin operations for managing questions, exams, and students
 */

require_once BASE_PATH . '/models/Question.php';
require_once BASE_PATH . '/models/Exam.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/services/SecurityService.php';

class AdminController {
    
    /**
     * Display admin dashboard
     */
    public function dashboard() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        require BASE_PATH . '/views/admin/dashboard.php';
    }
    
    /**
     * Display questions management page
     */
    public function questions() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $questions = Question::getAll();
        $editQuestion = null;
        
        // Check if editing a question
        if (isset($_GET['edit'])) {
            $editQuestion = Question::getById($_GET['edit']);
        }
        
        require BASE_PATH . '/views/admin/questions.php';
    }
    
    /**
     * Create a new question
     */
    public function createQuestion() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/questions');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/questions');
            exit;
        }
        
        $type = SecurityService::sanitizeInput($_POST['type'] ?? '');
        $content = $_POST['content'] ?? ''; // Keep HTML for question content
        $marks = intval($_POST['marks'] ?? 1);
        $createdBy = $_SESSION['user_id'];
        
        // Validate input
        if (empty($type) || empty($content)) {
            $_SESSION['error'] = 'Question type and content are required';
            header('Location: /admin/questions');
            exit;
        }
        
        // Prepare options based on question type
        $options = [];
        
        if ($type === 'multiple_choice') {
            // Get options from POST data
            $optionTexts = $_POST['options'] ?? [];
            $correctOption = $_POST['correct_option'] ?? '';
            
            if (count($optionTexts) < 2) {
                $_SESSION['error'] = 'Multiple choice questions must have at least 2 options';
                header('Location: /admin/questions');
                exit;
            }
            
            foreach ($optionTexts as $index => $text) {
                if (!empty(trim($text))) {
                    $options[] = [
                        'text' => trim($text),
                        'is_correct' => ($index == $correctOption)
                    ];
                }
            }
        } elseif ($type === 'select_all') {
            // Get options from POST data for select all
            $optionTexts = $_POST['select_all_options'] ?? [];
            $correctOptions = $_POST['correct_options'] ?? [];
            
            if (count($optionTexts) < 2) {
                $_SESSION['error'] = 'Select all questions must have at least 2 options';
                header('Location: /admin/questions');
                exit;
            }
            
            if (count($correctOptions) < 1) {
                $_SESSION['error'] = 'Select all questions must have at least 1 correct answer';
                header('Location: /admin/questions');
                exit;
            }
            
            foreach ($optionTexts as $index => $text) {
                if (!empty(trim($text))) {
                    $options[] = [
                        'text' => trim($text),
                        'is_correct' => in_array((string)$index, $correctOptions)
                    ];
                }
            }
        } elseif ($type === 'true_false') {
            // Auto-create True/False options
            $correctOption = $_POST['correct_option'] ?? '0';
            $options = [
                ['text' => 'True', 'is_correct' => ($correctOption == '0')],
                ['text' => 'False', 'is_correct' => ($correctOption == '1')]
            ];
        } elseif ($type === 'fill_blank') {
            // Store correct answer as a single option
            $correctAnswer = $_POST['correct_answer'] ?? '';
            if (empty(trim($correctAnswer))) {
                $_SESSION['error'] = 'Fill in the blank questions must have a correct answer';
                header('Location: /admin/questions');
                exit;
            }
            $options = [
                ['text' => trim($correctAnswer), 'is_correct' => true]
            ];
        } elseif ($type === 'short_answer') {
            // Store expected answer as a single option for reference
            $expectedAnswer = $_POST['expected_answer'] ?? '';
            if (empty(trim($expectedAnswer))) {
                $_SESSION['error'] = 'Short answer questions must have an expected answer';
                header('Location: /admin/questions');
                exit;
            }
            $options = [
                ['text' => trim($expectedAnswer), 'is_correct' => true]
            ];
        }
        
        // Create question
        $questionId = Question::create($type, $content, $marks, $createdBy, $options);
        
        if ($questionId) {
            $_SESSION['success'] = 'Question created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create question';
        }
        
        header('Location: /admin/questions');
        exit;
    }
    
    /**
     * Delete a question
     */
    public function deleteQuestion() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/questions');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/questions');
            exit;
        }
        
        $questionId = intval($_POST['question_id'] ?? 0);
        
        if ($questionId > 0) {
            if (Question::delete($questionId)) {
                $_SESSION['success'] = 'Question deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete question';
            }
        } else {
            $_SESSION['error'] = 'Invalid question ID';
        }
        
        header('Location: /admin/questions');
        exit;
    }
    
    /**
     * Display exams management page
     */
    public function exams() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        $exams = Exam::getAll();
        $editExam = null;
        $assignExamId = null;
        $assignStudentsExamId = null;
        
        // Check if editing an exam
        if (isset($_GET['edit'])) {
            $editExam = Exam::getById($_GET['edit']);
        }
        
        // Check if assigning questions to an exam
        if (isset($_GET['assign'])) {
            $assignExamId = intval($_GET['assign']);
            $assignExam = Exam::getById($assignExamId);
            $allQuestions = Question::getAll();
            $assignedQuestions = Exam::getAssignedQuestions($assignExamId);
            $assignedQuestionIds = array_column($assignedQuestions, 'question_id');
        }
        
        // Check if assigning students to an exam
        if (isset($_GET['assign_students'])) {
            $assignStudentsExamId = intval($_GET['assign_students']);
            $assignStudentsExam = Exam::getById($assignStudentsExamId);
            $allStudents = User::getByRole('student');
            $assignedStudents = Exam::getAssignedStudents($assignStudentsExamId);
            $assignedStudentIds = array_column($assignedStudents, 'student_id');
        }
        
        require BASE_PATH . '/views/admin/exams.php';
    }
    
    /**
     * Create a new exam
     */
    public function createExam() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/exams');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/exams');
            exit;
        }
        
        $title = SecurityService::sanitizeInput($_POST['title'] ?? '');
        $description = $_POST['description'] ?? ''; // Keep HTML for description
        $status = SecurityService::sanitizeInput($_POST['status'] ?? 'draft');
        $createdBy = $_SESSION['user_id'];
        
        // Validate input
        if (empty($title)) {
            $_SESSION['error'] = 'Exam title is required';
            header('Location: /admin/exams');
            exit;
        }
        
        // Create exam with default duration and marks (will be updated when questions are assigned)
        $examId = Exam::create($title, $description, 0, 0, $status, $createdBy);
        
        if ($examId) {
            $_SESSION['success'] = 'Exam created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create exam';
        }
        
        header('Location: /admin/exams');
        exit;
    }
    
    /**
     * Update an exam
     */
    public function updateExam() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/exams');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/exams');
            exit;
        }
        
        $examId = intval($_POST['exam_id'] ?? 0);
        $title = SecurityService::sanitizeInput($_POST['title'] ?? '');
        $description = $_POST['description'] ?? ''; // Keep HTML for description
        $duration = intval($_POST['duration'] ?? 0);
        $totalMarks = intval($_POST['total_marks'] ?? 0);
        $status = SecurityService::sanitizeInput($_POST['status'] ?? 'draft');
        
        // Validate input
        if (empty($title) || $examId <= 0) {
            $_SESSION['error'] = 'Invalid exam data';
            header('Location: /admin/exams');
            exit;
        }
        
        if (Exam::update($examId, $title, $description, $duration, $totalMarks, $status)) {
            $_SESSION['success'] = 'Exam updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update exam';
        }
        
        header('Location: /admin/exams');
        exit;
    }
    
    /**
     * Delete an exam
     */
    public function deleteExam() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/exams');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/exams');
            exit;
        }
        
        $examId = intval($_POST['exam_id'] ?? 0);
        
        if ($examId > 0) {
            if (Exam::delete($examId)) {
                $_SESSION['success'] = 'Exam deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete exam';
            }
        } else {
            $_SESSION['error'] = 'Invalid exam ID';
        }
        
        header('Location: /admin/exams');
        exit;
    }
    
    /**
     * Assign questions to an exam
     */
    public function assignQuestionsToExam() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/exams');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/exams');
            exit;
        }
        
        $examId = intval($_POST['exam_id'] ?? 0);
        $questionIds = $_POST['question_ids'] ?? [];
        
        // Validate input
        if ($examId <= 0) {
            $_SESSION['error'] = 'Invalid exam ID';
            header('Location: /admin/exams');
            exit;
        }
        
        // Convert question IDs to integers
        $questionIds = array_map('intval', $questionIds);
        $questionIds = array_filter($questionIds, function($id) { return $id > 0; });
        
        if (Exam::assignQuestions($examId, $questionIds)) {
            $_SESSION['success'] = 'Questions assigned successfully. Duration and marks calculated automatically.';
        } else {
            $_SESSION['error'] = 'Failed to assign questions';
        }
        
        header('Location: /admin/exams');
        exit;
    }
    
    /**
     * Assign students to an exam
     */
    public function assignStudentsToExam() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/exams');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/exams');
            exit;
        }
        
        $examId = intval($_POST['exam_id'] ?? 0);
        $studentIds = $_POST['student_ids'] ?? [];
        $retakeAllowed = isset($_POST['retake_allowed']) && $_POST['retake_allowed'] === '1';
        
        // Validate input
        if ($examId <= 0) {
            $_SESSION['error'] = 'Invalid exam ID';
            header('Location: /admin/exams');
            exit;
        }
        
        // Convert student IDs to integers
        $studentIds = array_map('intval', $studentIds);
        $studentIds = array_filter($studentIds, function($id) { return $id > 0; });
        
        if (Exam::assignToStudents($examId, $studentIds, $retakeAllowed)) {
            $_SESSION['success'] = 'Students assigned successfully.';
        } else {
            $_SESSION['error'] = 'Failed to assign students';
        }
        
        header('Location: /admin/exams');
        exit;
    }
    
    /**
     * Display students management page
     */
    public function students() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        // Get all students
        $students = User::getByRole('student');
        
        // Get exam history for each student
        foreach ($students as &$student) {
            $student['exam_history'] = User::getExamHistory($student['id']);
        }
        
        $editStudent = null;
        
        // Check if editing a student
        if (isset($_GET['edit'])) {
            $editStudent = User::getById($_GET['edit']);
            if ($editStudent && $editStudent['role'] !== 'student') {
                $editStudent = null; // Only allow editing students
            }
        }
        
        require BASE_PATH . '/views/admin/students.php';
    }
    
    /**
     * Create a new student
     */
    public function createStudent() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/students');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/students');
            exit;
        }
        
        $username = SecurityService::sanitizeInput($_POST['username'] ?? '');
        $email = SecurityService::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Username, email, and password are required';
            header('Location: /admin/students');
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format';
            header('Location: /admin/students');
            exit;
        }
        
        // Create student
        $studentId = User::create($username, $email, $password, 'student');
        
        if ($studentId) {
            $_SESSION['success'] = 'Student created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create student. Username or email may already exist.';
        }
        
        header('Location: /admin/students');
        exit;
    }
    
    /**
     * Update a student
     */
    public function updateStudent() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/students');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/students');
            exit;
        }
        
        $studentId = intval($_POST['student_id'] ?? 0);
        $username = SecurityService::sanitizeInput($_POST['username'] ?? '');
        $email = SecurityService::sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($email) || $studentId <= 0) {
            $_SESSION['error'] = 'Invalid student data';
            header('Location: /admin/students');
            exit;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format';
            header('Location: /admin/students');
            exit;
        }
        
        // Verify this is a student account
        $student = User::getById($studentId);
        if (!$student || $student['role'] !== 'student') {
            $_SESSION['error'] = 'Invalid student ID';
            header('Location: /admin/students');
            exit;
        }
        
        // Update student (password is optional)
        $passwordToUpdate = !empty($password) ? $password : null;
        
        if (User::update($studentId, $username, $email, $passwordToUpdate)) {
            $_SESSION['success'] = 'Student updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update student. Username or email may already exist.';
        }
        
        header('Location: /admin/students');
        exit;
    }
    
    /**
     * Delete a student
     */
    public function deleteStudent() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/students');
            exit;
        }
        
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityService::validateCSRFToken($token)) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: /admin/students');
            exit;
        }
        
        $studentId = intval($_POST['student_id'] ?? 0);
        
        if ($studentId > 0) {
            // Verify this is a student account
            $student = User::getById($studentId);
            if (!$student || $student['role'] !== 'student') {
                $_SESSION['error'] = 'Invalid student ID';
                header('Location: /admin/students');
                exit;
            }
            
            if (User::delete($studentId)) {
                $_SESSION['success'] = 'Student deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete student';
            }
        } else {
            $_SESSION['error'] = 'Invalid student ID';
        }
        
        header('Location: /admin/students');
        exit;
    }
    
    /**
     * Display analytics dashboard
     */
    public function analytics() {
        // Check if user is admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        try {
            $pdo = getDBConnection();
            
            // Get average scores per exam
            $avgScoresStmt = $pdo->query(
                "SELECT e.id, e.title, 
                        AVG(es.score) as avg_score, 
                        AVG(es.percentage) as avg_percentage,
                        COUNT(es.id) as total_sessions,
                        e.total_marks
                 FROM exams e
                 LEFT JOIN exam_sessions es ON e.id = es.exam_id 
                 WHERE es.status IN ('completed', 'auto_submitted')
                 GROUP BY e.id, e.title, e.total_marks
                 ORDER BY e.title"
            );
            $avgScores = $avgScoresStmt->fetchAll();
            
            // Get pass/fail rates per exam (assuming 60% is passing)
            $passingThreshold = 60;
            $passFailStmt = $pdo->query(
                "SELECT e.id, e.title,
                        COUNT(CASE WHEN es.percentage >= 60 THEN 1 END) as passed,
                        COUNT(CASE WHEN es.percentage < 60 THEN 1 END) as failed,
                        COUNT(es.id) as total
                 FROM exams e
                 LEFT JOIN exam_sessions es ON e.id = es.exam_id AND es.status IN ('completed', 'auto_submitted')
                 GROUP BY e.id, e.title
                 ORDER BY e.title"
            );
            $passFailRates = $passFailStmt->fetchAll();
            
            // Get question-level statistics (% correct)
            $questionStatsStmt = $pdo->query(
                "SELECT q.id, q.content, q.type,
                        COUNT(a.id) as total_answers,
                        COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                        ROUND((COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) / COUNT(a.id)) * 100, 2) as percentage_correct
                 FROM questions q
                 LEFT JOIN answers a ON q.id = a.question_id
                 WHERE a.id IS NOT NULL
                 GROUP BY q.id, q.content, q.type
                 HAVING total_answers > 0
                 ORDER BY percentage_correct ASC"
            );
            $questionStats = $questionStatsStmt->fetchAll();
            
            // Get student performance trends over time
            $performanceTrendsStmt = $pdo->query(
                "SELECT u.id, u.username,
                        es.exam_id, e.title as exam_title,
                        es.score, es.percentage,
                        es.end_time,
                        DATE(es.end_time) as exam_date
                 FROM users u
                 JOIN exam_sessions es ON u.id = es.student_id
                 JOIN exams e ON es.exam_id = e.id
                 WHERE u.role = 'student' AND es.status IN ('completed', 'auto_submitted')
                 ORDER BY u.username, es.end_time"
            );
            $performanceTrends = $performanceTrendsStmt->fetchAll();
            
            // Group performance trends by student
            $studentTrends = [];
            foreach ($performanceTrends as $trend) {
                $studentId = $trend['id'];
                if (!isset($studentTrends[$studentId])) {
                    $studentTrends[$studentId] = [
                        'username' => $trend['username'],
                        'exams' => []
                    ];
                }
                $studentTrends[$studentId]['exams'][] = [
                    'exam_title' => $trend['exam_title'],
                    'score' => $trend['score'],
                    'percentage' => $trend['percentage'],
                    'exam_date' => $trend['exam_date']
                ];
            }
            
            // Get overall statistics
            $overallStatsStmt = $pdo->query(
                "SELECT 
                    COUNT(DISTINCT e.id) as total_exams,
                    COUNT(DISTINCT u.id) as total_students,
                    COUNT(DISTINCT q.id) as total_questions,
                    COUNT(es.id) as total_sessions,
                    AVG(es.percentage) as overall_avg_percentage
                 FROM exams e
                 CROSS JOIN users u
                 CROSS JOIN questions q
                 LEFT JOIN exam_sessions es ON es.status IN ('completed', 'auto_submitted')
                 WHERE u.role = 'student'"
            );
            $overallStats = $overallStatsStmt->fetch();
            
            require BASE_PATH . '/views/admin/analytics.php';
        } catch (PDOException $e) {
            error_log("Error fetching analytics data: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to load analytics data';
            header('Location: /admin/dashboard');
            exit;
        }
    }
}
