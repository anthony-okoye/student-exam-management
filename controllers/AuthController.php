<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication operations including login and logout
 */

require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/services/SecurityService.php';

class AuthController {
    
    /**
     * Handle user login
     * 
     * @param string $username Username
     * @param string $password Password
     * @return array Result array with success status and message
     */
    public static function login($username, $password) {
        // Validate input
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username and password are required'
            ];
        }
        
        // Validate CSRF token for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityService::validateCSRFToken($token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid security token. Please try again.'
                ];
            }
        }
        
        // Get user by username
        $user = User::getByUsername($username);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Create session
        self::createSession($user->getId(), $user->getRole());
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'role' => $user->getRole()
        ];
    }
    
    /**
     * Handle user logout
     * 
     * @return void
     */
    public static function logout() {
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session for flash messages
        session_start();
        $_SESSION['flash_message'] = 'You have been logged out successfully';
    }
    
    /**
     * Create user session
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @return void
     */
    private static function createSession($userId, $role) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store user information in session
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Generate new CSRF token for the session
        SecurityService::generateCSRFToken();
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    public static function isAuthenticated() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if user has required role
     * 
     * @param string $requiredRole Required role (admin or student)
     * @return bool True if user has required role, false otherwise
     */
    public static function hasRole($requiredRole) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID if authenticated, null otherwise
     */
    public static function getCurrentUserId() {
        return self::isAuthenticated() ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current user role
     * 
     * @return string|null User role if authenticated, null otherwise
     */
    public static function getCurrentUserRole() {
        return self::isAuthenticated() ? $_SESSION['role'] : null;
    }
}
