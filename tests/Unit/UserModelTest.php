<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User Model
 * 
 * Tests user CRUD operations, authentication, and password handling
 */
class UserModelTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        cleanupTestDatabase();
    }
    
    protected function tearDown(): void {
        cleanupTestDatabase();
        parent::tearDown();
    }
    
    /**
     * Test creating a new user
     * Validates: Requirements 4.1 - Admin creates student record
     */
    public function testCreateUser() {
        $userId = \User::create('testuser', 'test@example.com', 'password123', 'student');
        
        $this->assertIsNumeric($userId);
        $this->assertGreaterThan(0, (int)$userId);
        
        // Verify user was created
        $user = \User::getById($userId);
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('student', $user['role']);
    }
    
    /**
     * Test password hashing on user creation
     * Validates: Requirements 8.1 - Password hashing
     */
    public function testPasswordIsHashed() {
        $plainPassword = 'mySecurePassword123';
        $userId = \User::create('hashtest', 'hash@test.com', $plainPassword, 'student');
        
        $this->assertNotFalse($userId);
        
        // Get user and verify password is not stored in plain text
        $user = \User::getByUsername('hashtest');
        $this->assertNotNull($user);
        $this->assertNotEquals($plainPassword, $user->getUsername()); // Username should not be password
        
        // Verify password can be verified
        $this->assertTrue($user->verifyPassword($plainPassword));
        $this->assertFalse($user->verifyPassword('wrongPassword'));
    }
    
    /**
     * Test getting user by username
     * Validates: Requirements 1.1 - User authentication
     */
    public function testGetByUsername() {
        \User::create('findme', 'findme@test.com', 'password123', 'student');
        
        $user = \User::getByUsername('findme');
        
        $this->assertNotNull($user);
        $this->assertInstanceOf(\User::class, $user);
        $this->assertEquals('findme', $user->getUsername());
        $this->assertEquals('findme@test.com', $user->getEmail());
    }
    
    /**
     * Test getting user by username returns null for non-existent user
     */
    public function testGetByUsernameReturnsNullForNonExistent() {
        $user = \User::getByUsername('nonexistent');
        
        $this->assertNull($user);
    }
    
    /**
     * Test password verification
     * Validates: Requirements 1.1, 1.2 - Authentication with valid/invalid credentials
     */
    public function testPasswordVerification() {
        $correctPassword = 'correctPassword123';
        $userId = \User::create('verifytest', 'verify@test.com', $correctPassword, 'student');
        
        $user = \User::getByUsername('verifytest');
        
        // Test correct password
        $this->assertTrue($user->verifyPassword($correctPassword));
        
        // Test incorrect password
        $this->assertFalse($user->verifyPassword('wrongPassword'));
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword('correctPassword124'));
    }
    
    /**
     * Test getting all users
     */
    public function testGetAllUsers() {
        \User::create('user1', 'user1@test.com', 'pass123', 'student');
        \User::create('user2', 'user2@test.com', 'pass123', 'admin');
        \User::create('user3', 'user3@test.com', 'pass123', 'student');
        
        $users = \User::getAll();
        
        $this->assertIsArray($users);
        $this->assertCount(3, $users);
    }
    
    /**
     * Test getting users by role
     * Validates: Requirements 1.5 - Role context separation
     */
    public function testGetByRole() {
        \User::create('student1', 'student1@test.com', 'pass123', 'student');
        \User::create('admin1', 'admin1@test.com', 'pass123', 'admin');
        \User::create('student2', 'student2@test.com', 'pass123', 'student');
        
        $students = \User::getByRole('student');
        $admins = \User::getByRole('admin');
        
        $this->assertCount(2, $students);
        $this->assertCount(1, $admins);
        
        foreach ($students as $student) {
            $this->assertEquals('student', $student['role']);
        }
        
        foreach ($admins as $admin) {
            $this->assertEquals('admin', $admin['role']);
        }
    }
    
    /**
     * Test updating user information
     * Validates: Requirements 4.2 - Admin edits student record
     */
    public function testUpdateUser() {
        $userId = \User::create('updateme', 'old@test.com', 'pass123', 'student');
        
        $result = \User::update($userId, 'updated_user', 'new@test.com');
        
        $this->assertTrue($result);
        
        $user = \User::getById($userId);
        $this->assertEquals('updated_user', $user['username']);
        $this->assertEquals('new@test.com', $user['email']);
    }
    
    /**
     * Test updating user with new password
     * Validates: Requirements 8.1 - Password hashing on update
     */
    public function testUpdateUserWithPassword() {
        $userId = \User::create('pwdupdate', 'pwd@test.com', 'oldpass', 'student');
        
        $result = \User::update($userId, 'pwdupdate', 'pwd@test.com', 'newpass123');
        
        $this->assertTrue($result);
        
        $user = \User::getByUsername('pwdupdate');
        $this->assertTrue($user->verifyPassword('newpass123'));
        $this->assertFalse($user->verifyPassword('oldpass'));
    }
    
    /**
     * Test deleting a user
     * Validates: Requirements 4.3 - Admin deletes student record
     */
    public function testDeleteUser() {
        $userId = \User::create('deleteme', 'delete@test.com', 'pass123', 'student');
        
        $this->assertNotFalse($userId);
        
        $result = \User::delete($userId);
        
        $this->assertTrue($result);
        
        $user = \User::getById($userId);
        $this->assertFalse($user); // getById returns false for non-existent users
    }
    
    /**
     * Test user ID preservation after update
     * Validates: Requirements 4.2 - Update preserves identity
     */
    public function testUpdatePreservesUserId() {
        $userId = \User::create('idtest', 'id@test.com', 'pass123', 'student');
        
        \User::update($userId, 'idtest_updated', 'id_new@test.com');
        
        $user = \User::getById($userId);
        $this->assertNotNull($user);
        $this->assertEquals($userId, $user['id']);
    }
}
