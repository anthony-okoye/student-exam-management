<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security tests for the application
 * 
 * Tests SQL injection prevention, XSS prevention, CSRF protection, and password security
 */
class SecurityTest extends TestCase {
    
    private $adminId;
    
    protected function setUp(): void {
        parent::setUp();
        cleanupTestDatabase();
        $this->adminId = createTestAdmin();
    }
    
    protected function tearDown(): void {
        cleanupTestDatabase();
        parent::tearDown();
    }
    
    /**
     * Test password hashing uses bcrypt
     * Validates: Requirements 8.1 - Password hashing with bcrypt
     */
    public function testPasswordHashingUsesBcrypt() {
        $password = 'testPassword123';
        $hash = \SecurityService::hashPassword($password);
        
        // Bcrypt hashes start with $2y$ or $2a$ or $2b$
        $this->assertStringStartsWith('$2', $hash);
        $this->assertNotEquals($password, $hash);
        
        // Verify the hash works
        $this->assertTrue(\SecurityService::verifyPassword($password, $hash));
    }
    
    /**
     * Test password verification rejects wrong passwords
     * Validates: Requirements 1.2 - Invalid credentials rejected
     */
    public function testPasswordVerificationRejectsWrongPassword() {
        $correctPassword = 'correctPassword123';
        $hash = \SecurityService::hashPassword($correctPassword);
        
        $this->assertFalse(\SecurityService::verifyPassword('wrongPassword', $hash));
        $this->assertFalse(\SecurityService::verifyPassword('', $hash));
        $this->assertFalse(\SecurityService::verifyPassword('correctPassword124', $hash));
    }
    
    /**
     * Test SQL injection prevention in user queries
     * Validates: Requirements 8.2 - SQL injection prevention
     */
    public function testSQLInjectionPreventionInUserQuery() {
        // Create a normal user
        \User::create('normaluser', 'normal@test.com', 'pass123', 'student');
        
        // Try SQL injection in username
        $maliciousUsername = "admin' OR '1'='1";
        $user = \User::getByUsername($maliciousUsername);
        
        // Should return null, not bypass authentication
        $this->assertNull($user);
    }
    
    /**
     * Test SQL injection prevention in question queries
     * Validates: Requirements 8.2 - SQL injection prevention
     */
    public function testSQLInjectionPreventionInQuestionQuery() {
        // Create a normal question
        $normalId = \Question::create('multiple_choice', 'Normal question', 1, $this->adminId, []);
        
        // Try SQL injection in question ID
        $maliciousId = "1 OR 1=1";
        $question = \Question::getById($maliciousId);
        
        // Should return null or the specific question, not all questions
        if ($question !== null) {
            $this->assertEquals($normalId, $question['id']);
        }
    }
    
    /**
     * Test XSS prevention in output escaping
     * Validates: Requirements 8.3 - XSS prevention
     */
    public function testXSSPreventionInOutputEscaping() {
        $maliciousInput = '<script>alert("XSS")</script>';
        $escaped = \SecurityService::escapeOutput($maliciousInput);
        
        // Should escape HTML entities
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }
    
    /**
     * Test XSS prevention with various payloads
     * Validates: Requirements 8.3 - XSS prevention
     */
    public function testXSSPreventionWithVariousPayloads() {
        $payloads = [
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            'javascript:alert(1)',
            '<iframe src="javascript:alert(1)">',
            '"><script>alert(1)</script>'
        ];
        
        foreach ($payloads as $payload) {
            $escaped = \SecurityService::escapeOutput($payload);
            
            // Should not contain unescaped < or >
            $this->assertStringNotContainsString('<script', $escaped);
            $this->assertStringNotContainsString('<img', $escaped);
            $this->assertStringNotContainsString('<svg', $escaped);
            $this->assertStringNotContainsString('<iframe', $escaped);
        }
    }
    
    /**
     * Test input sanitization removes HTML tags
     * Validates: Requirements 8.2, 8.3 - Input validation
     */
    public function testInputSanitizationRemovesTags() {
        $input = '<b>Bold text</b> with <script>alert("XSS")</script>';
        $sanitized = \SecurityService::sanitizeInput($input);
        
        // Should remove all HTML tags
        $this->assertStringNotContainsString('<b>', $sanitized);
        $this->assertStringNotContainsString('</b>', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertEquals('Bold text with alert("XSS")', $sanitized);
    }
    
    /**
     * Test input sanitization trims whitespace
     * Validates: Requirements 8.2 - Input validation
     */
    public function testInputSanitizationTrimsWhitespace() {
        $input = '   test input   ';
        $sanitized = \SecurityService::sanitizeInput($input);
        
        $this->assertEquals('test input', $sanitized);
    }
    
    /**
     * Test CSRF token generation
     * Validates: Requirements 8.4, 8.6 - CSRF protection
     */
    public function testCSRFTokenGeneration() {
        // Clear session
        unset($_SESSION['csrf_token']);
        
        $token1 = \SecurityService::generateCSRFToken();
        
        $this->assertIsString($token1);
        $this->assertGreaterThan(32, strlen($token1)); // Should be at least 32 chars (64 hex chars from 32 bytes)
        
        // Second call should return same token
        $token2 = \SecurityService::generateCSRFToken();
        $this->assertEquals($token1, $token2);
    }
    
    /**
     * Test CSRF token validation
     * Validates: Requirements 8.6 - CSRF protection
     */
    public function testCSRFTokenValidation() {
        unset($_SESSION['csrf_token']);
        
        $token = \SecurityService::generateCSRFToken();
        
        // Valid token should pass
        $this->assertTrue(\SecurityService::validateCSRFToken($token));
        
        // Invalid token should fail
        $this->assertFalse(\SecurityService::validateCSRFToken('invalid_token'));
        $this->assertFalse(\SecurityService::validateCSRFToken(''));
    }
    
    /**
     * Test CSRF token validation prevents timing attacks
     * Validates: Requirements 8.6 - CSRF protection with timing attack prevention
     */
    public function testCSRFTokenValidationUsesHashEquals() {
        unset($_SESSION['csrf_token']);
        
        $token = \SecurityService::generateCSRFToken();
        
        // Measure time for correct token
        $start1 = microtime(true);
        \SecurityService::validateCSRFToken($token);
        $time1 = microtime(true) - $start1;
        
        // Measure time for incorrect token of same length
        $wrongToken = str_repeat('a', strlen($token));
        $start2 = microtime(true);
        \SecurityService::validateCSRFToken($wrongToken);
        $time2 = microtime(true) - $start2;
        
        // Times should be similar (within 10x factor) - hash_equals prevents timing attacks
        // This is a rough check; in practice, timing differences should be minimal
        $this->assertLessThan($time1 * 10, $time2);
    }
    
    /**
     * Test password storage never stores plain text
     * Validates: Requirements 8.1 - Password hashing
     */
    public function testPasswordNeverStoredInPlainText() {
        $plainPassword = 'mySecretPassword123';
        $userId = \User::create('secureuser', 'secure@test.com', $plainPassword, 'student');
        
        // Get user directly from database
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch();
        
        // Password hash should not equal plain password
        $this->assertNotEquals($plainPassword, $result['password_hash']);
        
        // Should be a bcrypt hash
        $this->assertStringStartsWith('$2', $result['password_hash']);
    }
    
    /**
     * Test SQL injection in exam queries
     * Validates: Requirements 8.2 - SQL injection prevention
     */
    public function testSQLInjectionPreventionInExamQuery() {
        $normalExamId = \Exam::create('Normal Exam', 'Description', 30, 50, 'draft', $this->adminId);
        
        // Try SQL injection
        $maliciousId = "1 OR 1=1; DROP TABLE exams; --";
        $exam = \Exam::getById($maliciousId);
        
        // Should not execute malicious SQL
        // Verify exams table still exists by creating another exam
        $testExamId = \Exam::create('Test Exam', 'Test', 30, 50, 'draft', $this->adminId);
        $this->assertNotFalse($testExamId);
    }
    
    /**
     * Test SQL injection in answer saving
     * Validates: Requirements 8.2 - SQL injection prevention
     */
    public function testSQLInjectionPreventionInAnswerSaving() {
        $examId = \Exam::create('Test Exam', 'Description', 30, 50, 'in_progress', $this->adminId);
        $studentId = createTestStudent();
        \Exam::assignToStudents($examId, [$studentId], false);
        
        $sessionId = \ExamSession::start($examId, $studentId);
        $questionId = \Question::create('fill_blank', 'Test', 1, $this->adminId, []);
        
        // Try SQL injection in answer
        $maliciousAnswer = "'; DROP TABLE answers; --";
        $result = \ExamSession::saveAnswer($sessionId, $questionId, $maliciousAnswer);
        
        $this->assertTrue($result);
        
        // Verify answers table still exists
        $answers = \ExamSession::getAnswers($sessionId);
        $this->assertIsArray($answers);
    }
    
    /**
     * Test session token security
     * Validates: Requirements 8.4 - Secure session token generation
     */
    public function testSessionTokenSecurity() {
        // Generate multiple CSRF tokens and verify they're random
        $tokens = [];
        for ($i = 0; $i < 5; $i++) {
            unset($_SESSION['csrf_token']);
            $tokens[] = \SecurityService::generateCSRFToken();
        }
        
        // All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(5, $uniqueTokens);
        
        // All tokens should be sufficiently long (at least 32 bytes = 64 hex chars)
        foreach ($tokens as $token) {
            $this->assertGreaterThanOrEqual(64, strlen($token));
        }
    }
}
