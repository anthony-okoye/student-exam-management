<?php
/**
 * Online Examination System - Entry Point
 * 
 * This file serves as the main entry point for the application
 * and handles basic routing.
 */

// Start session
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include database configuration
require_once BASE_PATH . '/config/database.php';

// Include security service
require_once BASE_PATH . '/services/SecurityService.php';

// Include controllers and middleware
require_once BASE_PATH . '/controllers/AuthController.php';
require_once BASE_PATH . '/controllers/AdminController.php';
require_once BASE_PATH . '/controllers/StudentController.php';
require_once BASE_PATH . '/controllers/ExamController.php';
require_once BASE_PATH . '/middleware/auth.php';

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($script_name, '', $request_uri);
$path = trim($path, '/');
$path = parse_url($path, PHP_URL_PATH);

// Basic routing
switch ($path) {
    case '':
    case 'login':
        // Redirect if already authenticated
        redirectIfAuthenticated();
        
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $result = AuthController::login($username, $password);
            
            if ($result['success']) {
                // Redirect to appropriate dashboard
                $redirectUrl = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);
                
                if ($redirectUrl) {
                    header('Location: ' . $redirectUrl);
                } elseif ($result['role'] === 'admin') {
                    header('Location: /admin/dashboard');
                } else {
                    header('Location: /student/dashboard');
                }
                exit;
            } else {
                // Show error on login page
                $error = $result['message'];
                require BASE_PATH . '/views/auth/login.php';
            }
        } else {
            // Show login form
            require BASE_PATH . '/views/auth/login.php';
        }
        break;
    
    case 'logout':
        // Logout
        AuthController::logout();
        header('Location: /login');
        exit;
        break;
    
    case 'admin/dashboard':
        // Admin dashboard
        requireAdmin();
        $adminController = new AdminController();
        $adminController->dashboard();
        break;
    
    case 'admin/questions':
        // Admin questions management
        requireAdmin();
        $adminController = new AdminController();
        $adminController->questions();
        break;
    
    case 'admin/questions/create':
        // Create question
        requireAdmin();
        $adminController = new AdminController();
        $adminController->createQuestion();
        break;
    
    case 'admin/questions/delete':
        // Delete question
        requireAdmin();
        $adminController = new AdminController();
        $adminController->deleteQuestion();
        break;
    
    case 'admin/exams':
        // Admin exams management
        requireAdmin();
        $adminController = new AdminController();
        $adminController->exams();
        break;
    
    case 'admin/exams/create':
        // Create exam
        requireAdmin();
        $adminController = new AdminController();
        $adminController->createExam();
        break;
    
    case 'admin/exams/update':
        // Update exam
        requireAdmin();
        $adminController = new AdminController();
        $adminController->updateExam();
        break;
    
    case 'admin/exams/delete':
        // Delete exam
        requireAdmin();
        $adminController = new AdminController();
        $adminController->deleteExam();
        break;
    
    case 'admin/exams/assign-questions':
        // Assign questions to exam
        requireAdmin();
        $adminController = new AdminController();
        $adminController->assignQuestionsToExam();
        break;
    
    case 'admin/exams/assign-students':
        // Assign students to exam
        requireAdmin();
        $adminController = new AdminController();
        $adminController->assignStudentsToExam();
        break;
    
    case 'admin/students':
        // Admin students management
        requireAdmin();
        $adminController = new AdminController();
        $adminController->students();
        break;
    
    case 'admin/students/create':
        // Create student
        requireAdmin();
        $adminController = new AdminController();
        $adminController->createStudent();
        break;
    
    case 'admin/students/update':
        // Update student
        requireAdmin();
        $adminController = new AdminController();
        $adminController->updateStudent();
        break;
    
    case 'admin/students/delete':
        // Delete student
        requireAdmin();
        $adminController = new AdminController();
        $adminController->deleteStudent();
        break;
    
    case 'admin/analytics':
        // Admin analytics dashboard
        requireAdmin();
        $adminController = new AdminController();
        $adminController->analytics();
        break;
    
    case 'student/dashboard':
        // Student dashboard
        requireStudent();
        $studentController = new StudentController();
        $studentController->dashboard();
        break;
    
    case 'student/exam/instructions':
        // Exam instructions page
        requireStudent();
        $studentController = new StudentController();
        $studentController->examInstructions();
        break;
    
    case 'student/exam/start':
        // Start exam and show exam taking interface
        requireStudent();
        $examController = new ExamController();
        $examController->startExam();
        break;
    
    case 'student/exam/save-answer':
        // Save answer via AJAX
        requireStudent();
        $examController = new ExamController();
        $examController->saveAnswer();
        break;
    
    case 'student/exam/submit':
        // Submit exam
        requireStudent();
        $examController = new ExamController();
        $examController->submitExam();
        break;
    
    case 'student/exam/remaining-time':
        // Get remaining time via AJAX
        requireStudent();
        $examController = new ExamController();
        $examController->getRemainingTime();
        break;
    
    case 'student/exam/state':
        // Get exam state via AJAX (for page refresh restoration)
        requireStudent();
        $examController = new ExamController();
        $examController->getExamState();
        break;
    
    case 'student/exam/log-tab-switch':
        // Log tab switch event (anti-cheating)
        requireStudent();
        $examController = new ExamController();
        $examController->logTabSwitch();
        break;
    
    case 'student/exam/results':
        // View exam results
        requireStudent();
        $studentController = new StudentController();
        $studentController->results();
        break;
    
    case 'student/exam':
        // Exam taking interface (legacy route)
        requireStudent();
        echo "Please use the Start Exam button from the instructions page.";
        break;
    
    default:
        // 404 Not Found
        http_response_code(404);
        echo "404 - Page Not Found";
        break;
}
