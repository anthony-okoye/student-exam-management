<?php
/**
 * Authentication Middleware
 * 
 * Provides helper functions to protect routes and check user permissions
 */

require_once BASE_PATH . '/controllers/AuthController.php';

/**
 * Require authentication
 * Redirects to login page if user is not authenticated
 * 
 * @return void
 */
function requireAuth() {
    if (!AuthController::isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }
}

/**
 * Require admin role
 * Redirects to login page if user is not authenticated or not an admin
 * 
 * @return void
 */
function requireAdmin() {
    if (!AuthController::isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }
    
    if (!AuthController::hasRole('admin')) {
        http_response_code(403);
        die('Access Denied: Admin privileges required');
    }
}

/**
 * Require student role
 * Redirects to login page if user is not authenticated or not a student
 * 
 * @return void
 */
function requireStudent() {
    if (!AuthController::isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }
    
    if (!AuthController::hasRole('student')) {
        http_response_code(403);
        die('Access Denied: Student privileges required');
    }
}

/**
 * Redirect if authenticated
 * Useful for login page - redirects to appropriate dashboard if already logged in
 * 
 * @return void
 */
function redirectIfAuthenticated() {
    if (AuthController::isAuthenticated()) {
        $role = AuthController::getCurrentUserRole();
        if ($role === 'admin') {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /student/dashboard');
        }
        exit;
    }
}
