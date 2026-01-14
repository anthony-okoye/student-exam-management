<?php
/**
 * User Model
 * 
 * Handles user data operations including authentication
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/services/SecurityService.php';

class User {
    private $id;
    private $username;
    private $email;
    private $passwordHash;
    private $role;
    private $createdAt;
    private $updatedAt;
    
    /**
     * Get user by username
     * 
     * @param string $username The username to search for
     * @return User|null User object if found, null otherwise
     */
    public static function getByUsername($username) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $userData = $stmt->fetch();
            
            if (!$userData) {
                return null;
            }
            
            $user = new self();
            $user->id = $userData['id'];
            $user->username = $userData['username'];
            $user->email = $userData['email'];
            $user->passwordHash = $userData['password_hash'];
            $user->role = $userData['role'];
            $user->createdAt = $userData['created_at'];
            $user->updatedAt = $userData['updated_at'];
            
            return $user;
        } catch (PDOException $e) {
            error_log("Error fetching user by username: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify password against stored hash
     * 
     * @param string $password The password to verify
     * @return bool True if password matches, false otherwise
     */
    public function verifyPassword($password) {
        // Use bcrypt password verification
        return SecurityService::verifyPassword($password, $this->passwordHash);
    }
    
    /**
     * Get user ID
     * 
     * @return int User ID
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Get username
     * 
     * @return string Username
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * Get email
     * 
     * @return string Email
     */
    public function getEmail() {
        return $this->email;
    }
    
    /**
     * Get user role
     * 
     * @return string Role (admin or student)
     */
    public function getRole() {
        return $this->role;
    }
    
    /**
     * Get created at timestamp
     * 
     * @return string Created at timestamp
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }
    
    /**
     * Get updated at timestamp
     * 
     * @return string Updated at timestamp
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
    
    /**
     * Create a new user with hashed password
     * 
     * @param string $username Username
     * @param string $email Email
     * @param string $password Plain text password (will be hashed)
     * @param string $role User role (admin or student)
     * @return int|false User ID if successful, false otherwise
     */
    public static function create($username, $email, $password, $role = 'student') {
        try {
            $pdo = getDBConnection();
            
            // Hash the password
            $passwordHash = SecurityService::hashPassword($password);
            
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash, role) 
                 VALUES (:username, :email, :password_hash, :role)"
            );
            
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role
            ]);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users
     * 
     * @return array Array of user data
     */
    public static function getAll() {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get users by role
     * 
     * @param string $role The role to filter by (admin or student)
     * @return array Array of user data
     */
    public static function getByRole($role) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, updated_at FROM users WHERE role = :role ORDER BY created_at DESC");
            $stmt->execute(['role' => $role]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users by role: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|null User data if found, null otherwise
     */
    public static function getById($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user information
     * 
     * @param int $id User ID
     * @param string $username Username
     * @param string $email Email
     * @param string|null $password New password (optional, will be hashed if provided)
     * @return bool True if successful, false otherwise
     */
    public static function update($id, $username, $email, $password = null) {
        try {
            $pdo = getDBConnection();
            
            if ($password !== null && !empty($password)) {
                // Update with new password
                $passwordHash = SecurityService::hashPassword($password);
                $stmt = $pdo->prepare(
                    "UPDATE users SET username = :username, email = :email, password_hash = :password_hash 
                     WHERE id = :id"
                );
                $stmt->execute([
                    'id' => $id,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $passwordHash
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare(
                    "UPDATE users SET username = :username, email = :email 
                     WHERE id = :id"
                );
                $stmt->execute([
                    'id' => $id,
                    'username' => $username,
                    'email' => $email
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a user
     * 
     * @param int $id User ID
     * @return bool True if successful, false otherwise
     */
    public static function delete($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get exam history for a user
     * 
     * @param int $userId User ID
     * @return array Array of exam session data with exam details
     */
    public static function getExamHistory($userId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    es.id as session_id,
                    es.exam_id,
                    e.title as exam_title,
                    es.start_time,
                    es.end_time,
                    es.status,
                    es.score,
                    es.percentage,
                    e.total_marks
                FROM exam_sessions es
                JOIN exams e ON es.exam_id = e.id
                WHERE es.student_id = :user_id
                ORDER BY es.start_time DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching exam history: " . $e->getMessage());
            return [];
        }
    }
}
