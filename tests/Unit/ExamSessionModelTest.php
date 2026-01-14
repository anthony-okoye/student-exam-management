<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ExamSession Model and Scoring Logic
 * 
 * Tests exam session operations, answer saving, and scoring algorithms
 */
class ExamSessionModelTest extends TestCase {
    
    private $adminId;
    private $studentId;
    private $examId;
    
    protected function setUp(): void {
        parent::setUp();
        cleanupTestDatabase();
        $this->adminId = createTestAdmin();
        $this->studentId = createTestStudent();
        
        // Create a test exam with questions
        $this->examId = \Exam::create('Test Exam', 'Description', 30, 10, 'in_progress', $this->adminId);
        
        // Assign exam to student
        \Exam::assignToStudents($this->examId, [$this->studentId], false);
    }
    
    protected function tearDown(): void {
        cleanupTestDatabase();
        parent::tearDown();
    }
    
    /**
     * Test starting an exam session
     * Validates: Requirements 6.1 - Session initialization
     */
    public function testStartExamSession() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $this->assertIsNumeric($sessionId);
        $this->assertGreaterThan(0, (int)$sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals($this->examId, $session['exam_id']);
        $this->assertEquals($this->studentId, $session['student_id']);
        $this->assertEquals('in_progress', $session['status']);
    }
    
    /**
     * Test saving an answer
     * Validates: Requirements 6.4, 10.1 - Answer persistence
     */
    public function testSaveAnswer() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $questionId = \Question::create('fill_blank', 'Test question', 1, $this->adminId, []);
        
        $result = \ExamSession::saveAnswer($sessionId, $questionId, 'My answer');
        
        $this->assertTrue($result);
        
        $answers = \ExamSession::getAnswers($sessionId);
        $this->assertCount(1, $answers);
        $this->assertEquals('My answer', $answers[0]['answer_text']);
    }
    
    /**
     * Test updating an existing answer
     * Validates: Requirements 10.1 - Answer updates
     */
    public function testUpdateAnswer() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $questionId = \Question::create('fill_blank', 'Test question', 1, $this->adminId, []);
        
        \ExamSession::saveAnswer($sessionId, $questionId, 'First answer');
        \ExamSession::saveAnswer($sessionId, $questionId, 'Updated answer');
        
        $answers = \ExamSession::getAnswers($sessionId);
        $this->assertCount(1, $answers);
        $this->assertEquals('Updated answer', $answers[0]['answer_text']);
    }
    
    /**
     * Test submitting an exam
     * Validates: Requirements 6.7 - Manual submission
     */
    public function testSubmitExam() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $result = \ExamSession::submit($sessionId);
        
        $this->assertTrue($result);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals('completed', $session['status']);
        $this->assertNotNull($session['end_time']);
    }
    
    /**
     * Test auto-submitting an exam
     * Validates: Requirements 6.6 - Auto-submission on timer expiry
     */
    public function testAutoSubmitExam() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $result = \ExamSession::autoSubmit($sessionId);
        
        $this->assertTrue($result);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals('auto_submitted', $session['status']);
        $this->assertNotNull($session['end_time']);
    }
    
    /**
     * Test scoring multiple choice question correctly
     * Validates: Requirements 7.2 - Multiple choice scoring
     */
    public function testScoreMultipleChoiceCorrect() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Wrong 1', 'is_correct' => false],
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong 2', 'is_correct' => false]
        ];
        
        $questionId = \Question::create('multiple_choice', 'Test question', 5, $this->adminId, $options);
        
        // Get the correct option ID
        $question = \Question::getById($questionId);
        $correctOptionId = null;
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctOptionId = $option['id'];
                break;
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $questionId, [$correctOptionId]);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(5, $session['score']);
    }
    
    /**
     * Test scoring multiple choice question incorrectly
     * Validates: Requirements 7.2 - Multiple choice scoring
     */
    public function testScoreMultipleChoiceIncorrect() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Wrong 1', 'is_correct' => false],
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong 2', 'is_correct' => false]
        ];
        
        $questionId = \Question::create('multiple_choice', 'Test question', 5, $this->adminId, $options);
        
        // Get a wrong option ID
        $question = \Question::getById($questionId);
        $wrongOptionId = null;
        foreach ($question['options'] as $option) {
            if (!$option['is_correct']) {
                $wrongOptionId = $option['id'];
                break;
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $questionId, [$wrongOptionId]);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(0, $session['score']);
    }
    
    /**
     * Test scoring true/false question
     * Validates: Requirements 7.4 - True/false scoring
     */
    public function testScoreTrueFalse() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'True', 'is_correct' => true],
            ['text' => 'False', 'is_correct' => false]
        ];
        
        $questionId = \Question::create('true_false', 'Test question', 2, $this->adminId, $options);
        
        $question = \Question::getById($questionId);
        $correctOptionId = null;
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctOptionId = $option['id'];
                break;
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $questionId, [$correctOptionId]);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(2, $session['score']);
    }
    
    /**
     * Test scoring fill in the blank with case-insensitive comparison
     * Validates: Requirements 7.5 - Fill in blank case-insensitive scoring
     */
    public function testScoreFillBlankCaseInsensitive() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Paris', 'is_correct' => true]
        ];
        
        $questionId = \Question::create('fill_blank', 'Capital of France?', 3, $this->adminId, $options);
        
        // Test with different case
        \ExamSession::saveAnswer($sessionId, $questionId, 'PARIS');
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(3, $session['score']);
    }
    
    /**
     * Test scoring fill in the blank with whitespace trimming
     * Validates: Requirements 7.5 - Fill in blank whitespace handling
     */
    public function testScoreFillBlankWithWhitespace() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Paris', 'is_correct' => true]
        ];
        
        $questionId = \Question::create('fill_blank', 'Capital of France?', 3, $this->adminId, $options);
        
        // Test with extra whitespace
        \ExamSession::saveAnswer($sessionId, $questionId, '  Paris  ');
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(3, $session['score']);
    }
    
    /**
     * Test scoring select all that apply correctly
     * Validates: Requirements 7.3 - Select all scoring
     */
    public function testScoreSelectAllCorrect() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Correct 1', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false],
            ['text' => 'Correct 2', 'is_correct' => true]
        ];
        
        $questionId = \Question::create('select_all', 'Test question', 4, $this->adminId, $options);
        
        $question = \Question::getById($questionId);
        $correctOptionIds = [];
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctOptionIds[] = $option['id'];
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $questionId, $correctOptionIds);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(4, $session['score']);
    }
    
    /**
     * Test scoring select all that apply with partial selection
     * Validates: Requirements 7.3 - Select all requires exact match
     */
    public function testScoreSelectAllPartial() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $options = [
            ['text' => 'Correct 1', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false],
            ['text' => 'Correct 2', 'is_correct' => true]
        ];
        
        $questionId = \Question::create('select_all', 'Test question', 4, $this->adminId, $options);
        
        $question = \Question::getById($questionId);
        $partialOptionIds = [];
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $partialOptionIds[] = $option['id'];
                break; // Only select one correct answer
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $questionId, $partialOptionIds);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(0, $session['score']); // Should be 0 for partial match
    }
    
    /**
     * Test calculating percentage
     * Validates: Requirements 7.7 - Results display with percentage
     */
    public function testCalculatePercentage() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        // Create questions worth 10 marks total
        $q1 = \Question::create('multiple_choice', 'Q1', 5, $this->adminId, [
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false]
        ]);
        
        $q2 = \Question::create('multiple_choice', 'Q2', 5, $this->adminId, [
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false]
        ]);
        
        \Exam::assignQuestions($this->examId, [$q1, $q2]);
        
        // Answer only first question correctly
        $question1 = \Question::getById($q1);
        $correctOptionId = null;
        foreach ($question1['options'] as $option) {
            if ($option['is_correct']) {
                $correctOptionId = $option['id'];
                break;
            }
        }
        
        \ExamSession::saveAnswer($sessionId, $q1, [$correctOptionId]);
        \ExamSession::submit($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(5, $session['score']);
        $this->assertEquals(50.0, $session['percentage']);
    }
    
    /**
     * Test getting remaining time
     * Validates: Requirements 9.2 - Server-side time calculation
     */
    public function testGetRemainingTime() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $remainingTime = \ExamSession::getRemainingTime($sessionId);
        
        $this->assertIsInt($remainingTime);
        $this->assertGreaterThan(0, $remainingTime);
        $this->assertLessThanOrEqual(30 * 60, $remainingTime); // Should be <= 30 minutes
    }
    
    /**
     * Test tab switch count increment
     * Validates: Requirements 11.1 - Tab switch logging
     */
    public function testIncrementTabSwitchCount() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $count1 = \ExamSession::incrementTabSwitchCount($sessionId);
        $this->assertEquals(1, $count1);
        
        $count2 = \ExamSession::incrementTabSwitchCount($sessionId);
        $this->assertEquals(2, $count2);
        
        $count3 = \ExamSession::incrementTabSwitchCount($sessionId);
        $this->assertEquals(3, $count3);
    }
    
    /**
     * Test completed exam immutability
     * Validates: Requirements 6.8 - Completed exam cannot be modified
     */
    public function testCompletedExamImmutability() {
        $sessionId = \ExamSession::start($this->examId, $this->studentId);
        
        $questionId = \Question::create('fill_blank', 'Test', 1, $this->adminId, []);
        
        \ExamSession::saveAnswer($sessionId, $questionId, 'Answer 1');
        \ExamSession::submit($sessionId);
        
        // Try to save answer after submission
        $result = \ExamSession::saveAnswer($sessionId, $questionId, 'Answer 2');
        
        $this->assertFalse($result);
    }
}
