<?php
/**
 * Security Service
 * 
 * Provides security utilities including CSRF protection and XSS prevention
 */

class SecurityService {
    
    /**
     * Generate a CSRF token and store it in the session
     * 
     * @return string The generated CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate a CSRF token against the session token
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize output to prevent XSS attacks
     * 
     * @param string $output The output to sanitize
     * @return string The sanitized output
     */
    public static function escapeOutput($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate and sanitize input
     * 
     * @param string $input The input to sanitize
     * @return string The sanitized input
     */
    public static function sanitizeInput($input) {
        return trim(strip_tags($input));
    }
    
    /**
     * Hash a password using bcrypt
     * 
     * @param string $password The password to hash
     * @return string The hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool True if password matches, false otherwise
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

