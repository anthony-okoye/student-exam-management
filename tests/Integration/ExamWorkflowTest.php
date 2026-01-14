<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for complete exam workflows
 * 
 * Tests end-to-end scenarios from exam creation to result viewing
 */
class ExamWorkflowTest extends TestCase {
    
    private $adminId;
    private $studentId;
    
    protected function setUp(): void {
        parent::setUp();
        cleanupTestDatabase();
        $this->adminId = createTestAdmin();
        $this->studentId = createTestStudent();
    }
    
    protected function tearDown(): void {
        cleanupTestDatabase();
        parent::tearDown();
    }
    
    /**
     * Test complete exam creation and taking workflow
     * Validates: End-to-end workflow from creation to completion
     */
    public function testCompleteExamWorkflow() {
        // Step 1: Admin creates questions
        $q1Options = [
            ['text' => 'Wrong 1', 'is_correct' => false],
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong 2', 'is_correct' => false]
        ];
        $q1 = \Question::create('multiple_choice', 'What is 2+2?', 2, $this->adminId, $q1Options);
        
        $q2Options = [
            ['text' => 'True', 'is_correct' => true],
            ['text' => 'False', 'is_correct' => false]
        ];
        $q2 = \Question::create('true_false', 'The Earth is round.', 2, $this->adminId, $q2Options);
        
        $q3Options = [
            ['text' => 'Paris', 'is_correct' => true]
        ];
        $q3 = \Question::create('fill_blank', 'Capital of France?', 3, $this->adminId, $q3Options);
        
        $this->assertNotFalse($q1);
        $this->assertNotFalse($q2);
        $this->assertNotFalse($q3);
        
        // Step 2: Admin creates exam
        $examId = \Exam::create(
            'General Knowledge Test',
            'A test of general knowledge',
            0,
            0,
            'draft',
            $this->adminId
        );
        $this->assertNotFalse($examId);
        
        // Step 3: Admin assigns questions to exam
        $result = \Exam::assignQuestions($examId, [$q1, $q2, $q3]);
        $this->assertTrue($result);
        
        // Verify exam duration and marks calculated
        $exam = \Exam::getById($examId);
        $this->assertEquals(6, $exam['duration']); // 3 questions × 2 minutes
        $this->assertEquals(7, $exam['total_marks']); // 2 + 2 + 3
        
        // Step 4: Admin changes exam status to in_progress
        \Exam::update($examId, $exam['title'], $exam['description'], $exam['duration'], $exam['total_marks'], 'in_progress');
        
        // Step 5: Admin assigns exam to student
        $result = \Exam::assignToStudents($examId, [$this->studentId], false);
        $this->assertTrue($result);
        
        // Step 6: Student views assigned exams
        $assignedExams = \Exam::getAssignedToStudent($this->studentId);
        $this->assertCount(1, $assignedExams);
        $this->assertEquals($examId, $assignedExams[0]['id']);
        
        // Step 7: Student starts exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        $this->assertNotFalse($sessionId);
        
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals('in_progress', $session['status']);
        
        // Step 8: Student answers questions
        $question1 = \Question::getById($q1);
        $correctOption1 = null;
        foreach ($question1['options'] as $option) {
            if ($option['is_correct']) {
                $correctOption1 = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q1, [$correctOption1]);
        
        $question2 = \Question::getById($q2);
        $correctOption2 = null;
        foreach ($question2['options'] as $option) {
            if ($option['is_correct']) {
                $correctOption2 = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q2, [$correctOption2]);
        
        \ExamSession::saveAnswer($sessionId, $q3, 'Paris');
        
        // Step 9: Student submits exam
        $result = \ExamSession::submit($sessionId);
        $this->assertTrue($result);
        
        // Step 10: Verify results
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals('completed', $session['status']);
        $this->assertEquals(7, $session['score']); // All correct
        $this->assertEquals(100.0, $session['percentage']);
        
        // Step 11: Verify answers are stored
        $answers = \ExamSession::getAnswers($sessionId);
        $this->assertCount(3, $answers);
        
        foreach ($answers as $answer) {
            $this->assertTrue((bool)$answer['is_correct']);
        }
    }
    
    /**
     * Test exam workflow with partial correct answers
     * Validates: Scoring with mixed correct/incorrect answers
     */
    public function testExamWorkflowWithPartialScore() {
        // Create questions
        $q1Options = [
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false]
        ];
        $q1 = \Question::create('multiple_choice', 'Question 1', 5, $this->adminId, $q1Options);
        
        $q2Options = [
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false]
        ];
        $q2 = \Question::create('multiple_choice', 'Question 2', 5, $this->adminId, $q2Options);
        
        // Create and setup exam
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1, $q2]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Start exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        
        // Answer first question correctly
        $question1 = \Question::getById($q1);
        $correctOption1 = null;
        foreach ($question1['options'] as $option) {
            if ($option['is_correct']) {
                $correctOption1 = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q1, [$correctOption1]);
        
        // Answer second question incorrectly
        $question2 = \Question::getById($q2);
        $wrongOption2 = null;
        foreach ($question2['options'] as $option) {
            if (!$option['is_correct']) {
                $wrongOption2 = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q2, [$wrongOption2]);
        
        // Submit exam
        \ExamSession::submit($sessionId);
        
        // Verify partial score
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(5, $session['score']); // Only first question correct
        $this->assertEquals(50.0, $session['percentage']);
    }
    
    /**
     * Test exam workflow with auto-submission
     * Validates: Auto-submission when timer expires
     */
    public function testExamWorkflowWithAutoSubmission() {
        // Create question
        $q1Options = [
            ['text' => 'Correct', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false]
        ];
        $q1 = \Question::create('multiple_choice', 'Question 1', 10, $this->adminId, $q1Options);
        
        // Create and setup exam
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Start exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        
        // Answer question
        $question1 = \Question::getById($q1);
        $correctOption1 = null;
        foreach ($question1['options'] as $option) {
            if ($option['is_correct']) {
                $correctOption1 = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q1, [$correctOption1]);
        
        // Auto-submit (simulating timer expiry)
        $result = \ExamSession::autoSubmit($sessionId);
        $this->assertTrue($result);
        
        // Verify auto-submitted status
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals('auto_submitted', $session['status']);
        $this->assertEquals(10, $session['score']);
    }
    
    /**
     * Test retake prevention workflow
     * Validates: Student cannot retake completed exam
     */
    public function testRetakePreventionWorkflow() {
        // Create and setup exam
        $q1 = \Question::create('multiple_choice', 'Q1', 5, $this->adminId, [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false]
        ]);
        
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // First attempt
        $sessionId1 = \ExamSession::start($examId, $this->studentId);
        $this->assertNotFalse($sessionId1);
        
        \ExamSession::submit($sessionId1);
        
        // Try to start again (should return false or same session)
        $sessionId2 = \ExamSession::start($examId, $this->studentId);
        $this->assertFalse($sessionId2);
        
        // Verify cannot take exam
        $canTake = \Exam::canStudentTakeExam($examId, $this->studentId);
        $this->assertFalse($canTake['allowed']);
    }
    
    /**
     * Test exam workflow with retake allowed
     * Validates: Student can retake exam when allowed
     */
    public function testRetakeAllowedWorkflow() {
        // Create and setup exam with retake allowed
        $q1 = \Question::create('multiple_choice', 'Q1', 5, $this->adminId, [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false]
        ]);
        
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], true); // Retake allowed
        
        // First attempt
        $sessionId1 = \ExamSession::start($examId, $this->studentId);
        \ExamSession::submit($sessionId1);
        
        // Verify can take exam again
        $canTake = \Exam::canStudentTakeExam($examId, $this->studentId);
        $this->assertTrue($canTake['allowed']);
    }
    
    /**
     * Test exam workflow with answer updates
     * Validates: Student can update answers before submission
     */
    public function testExamWorkflowWithAnswerUpdates() {
        // Create question
        $q1Options = [
            ['text' => 'Option A', 'is_correct' => false],
            ['text' => 'Option B', 'is_correct' => true]
        ];
        $q1 = \Question::create('multiple_choice', 'Question 1', 5, $this->adminId, $q1Options);
        
        // Create and setup exam
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Start exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        
        // Get options
        $question1 = \Question::getById($q1);
        $optionA = $question1['options'][0]['id'];
        $optionB = $question1['options'][1]['id'];
        
        // First answer (wrong)
        \ExamSession::saveAnswer($sessionId, $q1, [$optionA]);
        
        // Update answer (correct)
        \ExamSession::saveAnswer($sessionId, $q1, [$optionB]);
        
        // Submit
        \ExamSession::submit($sessionId);
        
        // Verify correct answer was used
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(5, $session['score']);
        
        // Verify only one answer record exists
        $answers = \ExamSession::getAnswers($sessionId);
        $this->assertCount(1, $answers);
    }
    
    /**
     * Test exam workflow with tab switching
     * Validates: Tab switches are logged during exam
     */
    public function testExamWorkflowWithTabSwitching() {
        // Create and setup exam
        $q1 = \Question::create('multiple_choice', 'Q1', 5, $this->adminId, [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false]
        ]);
        
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Start exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        
        // Simulate tab switches
        \ExamSession::incrementTabSwitchCount($sessionId);
        \ExamSession::incrementTabSwitchCount($sessionId);
        \ExamSession::incrementTabSwitchCount($sessionId);
        
        // Answer and submit
        $question1 = \Question::getById($q1);
        $correctOption = null;
        foreach ($question1['options'] as $option) {
            if ($option['is_correct']) {
                $correctOption = $option['id'];
                break;
            }
        }
        \ExamSession::saveAnswer($sessionId, $q1, [$correctOption]);
        \ExamSession::submit($sessionId);
        
        // Verify tab switch count in results
        $session = \ExamSession::getById($sessionId);
        $this->assertEquals(3, $session['tab_switch_count']);
    }
    
    /**
     * Test cascading deletion workflow
     * Validates: Deleting exam removes associated sessions
     */
    public function testCascadingDeletionWorkflow() {
        // Create and setup exam
        $q1 = \Question::create('multiple_choice', 'Q1', 5, $this->adminId, [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => false]
        ]);
        
        $examId = \Exam::create('Test', 'Test', 0, 0, 'in_progress', $this->adminId);
        \Exam::assignQuestions($examId, [$q1]);
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Start and complete exam
        $sessionId = \ExamSession::start($examId, $this->studentId);
        \ExamSession::submit($sessionId);
        
        // Verify session exists
        $session = \ExamSession::getById($sessionId);
        $this->assertNotNull($session);
        
        // Delete exam
        \Exam::delete($examId);
        
        // Verify exam is deleted
        $exam = \Exam::getById($examId);
        $this->assertNull($exam);
        
        // Verify session is also deleted (cascading)
        $session = \ExamSession::getById($sessionId);
        $this->assertNull($session);
    }
}
