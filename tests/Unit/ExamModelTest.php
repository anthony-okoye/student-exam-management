<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Exam Model
 * 
 * Tests exam CRUD operations, question assignments, and student assignments
 */
class ExamModelTest extends TestCase {
    
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
     * Test creating an exam
     * Validates: Requirements 3.1 - Admin creates exam
     */
    public function testCreateExam() {
        $examId = \Exam::create(
            'Math Test',
            'Basic mathematics exam',
            60,
            100,
            'draft',
            $this->adminId
        );
        
        $this->assertIsNumeric($examId);
        $this->assertGreaterThan(0, (int)$examId);
        
        $exam = \Exam::getById($examId);
        $this->assertNotNull($exam);
        $this->assertEquals('Math Test', $exam['title']);
        $this->assertEquals('Basic mathematics exam', $exam['description']);
        $this->assertEquals(60, $exam['duration']);
        $this->assertEquals(100, $exam['total_marks']);
        $this->assertEquals('draft', $exam['status']);
    }
    
    /**
     * Test getting all exams
     */
    public function testGetAllExams() {
        \Exam::create('Exam 1', 'Description 1', 30, 50, 'draft', $this->adminId);
        \Exam::create('Exam 2', 'Description 2', 45, 75, 'in_progress', $this->adminId);
        \Exam::create('Exam 3', 'Description 3', 60, 100, 'closed', $this->adminId);
        
        $exams = \Exam::getAll();
        
        $this->assertIsArray($exams);
        $this->assertCount(3, $exams);
    }
    
    /**
     * Test updating an exam
     * Validates: Requirements 3.2 - Admin edits exam
     */
    public function testUpdateExam() {
        $examId = \Exam::create(
            'Original Title',
            'Original Description',
            30,
            50,
            'draft',
            $this->adminId
        );
        
        $result = \Exam::update(
            $examId,
            'Updated Title',
            'Updated Description',
            45,
            75,
            'in_progress'
        );
        
        $this->assertTrue($result);
        
        $exam = \Exam::getById($examId);
        $this->assertEquals('Updated Title', $exam['title']);
        $this->assertEquals('Updated Description', $exam['description']);
        $this->assertEquals(45, $exam['duration']);
        $this->assertEquals(75, $exam['total_marks']);
        $this->assertEquals('in_progress', $exam['status']);
    }
    
    /**
     * Test deleting an exam
     * Validates: Requirements 3.3 - Admin deletes exam
     */
    public function testDeleteExam() {
        $examId = \Exam::create(
            'Delete Me',
            'This exam will be deleted',
            30,
            50,
            'draft',
            $this->adminId
        );
        
        $result = \Exam::delete($examId);
        
        $this->assertTrue($result);
        
        $exam = \Exam::getById($examId);
        $this->assertFalse($exam); // getById returns false for non-existent exams
    }
    
    /**
     * Test assigning questions to an exam
     * Validates: Requirements 3.6 - Question assignment creates links
     */
    public function testAssignQuestionsToExam() {
        $examId = \Exam::create('Test Exam', 'Description', 0, 0, 'draft', $this->adminId);
        
        // Create questions
        $q1 = \Question::create('multiple_choice', 'Question 1', 2, $this->adminId, []);
        $q2 = \Question::create('true_false', 'Question 2', 1, $this->adminId, []);
        $q3 = \Question::create('fill_blank', 'Question 3', 3, $this->adminId, []);
        
        $result = \Exam::assignQuestions($examId, [$q1, $q2, $q3]);
        
        $this->assertTrue($result);
        
        // Verify questions are assigned
        $assignedQuestions = \Exam::getAssignedQuestions($examId);
        $this->assertCount(3, $assignedQuestions);
        
        // Verify exam duration and total marks are calculated
        $exam = \Exam::getById($examId);
        $this->assertEquals(6, $exam['duration']); // 3 questions × 2 minutes
        $this->assertEquals(6, $exam['total_marks']); // 2 + 1 + 3
    }
    
    /**
     * Test duration calculation based on question count
     * Validates: Requirements 3.4 - Duration calculation
     */
    public function testDurationCalculation() {
        $examId = \Exam::create('Test Exam', 'Description', 0, 0, 'draft', $this->adminId);
        
        // Create 5 questions
        $questionIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $questionIds[] = \Question::create('multiple_choice', "Question $i", 1, $this->adminId, []);
        }
        
        \Exam::assignQuestions($examId, $questionIds);
        
        $exam = \Exam::getById($examId);
        $this->assertEquals(10, $exam['duration']); // 5 questions × 2 minutes
    }
    
    /**
     * Test removing questions from exam preserves questions
     * Validates: Requirements 3.7 - Question removal preserves questions
     */
    public function testRemovingQuestionsPreservesQuestions() {
        $examId = \Exam::create('Test Exam', 'Description', 0, 0, 'draft', $this->adminId);
        
        $q1 = \Question::create('multiple_choice', 'Question 1', 1, $this->adminId, []);
        $q2 = \Question::create('true_false', 'Question 2', 1, $this->adminId, []);
        
        \Exam::assignQuestions($examId, [$q1, $q2]);
        
        // Remove all questions by assigning empty array
        \Exam::assignQuestions($examId, []);
        
        // Verify questions still exist
        $question1 = \Question::getById($q1);
        $question2 = \Question::getById($q2);
        
        $this->assertNotNull($question1);
        $this->assertNotNull($question2);
        
        // Verify no questions assigned to exam
        $assignedQuestions = \Exam::getAssignedQuestions($examId);
        $this->assertCount(0, $assignedQuestions);
    }
    
    /**
     * Test assigning exam to students
     * Validates: Requirements 4.4 - Exam assignment to students
     */
    public function testAssignExamToStudents() {
        $examId = \Exam::create('Test Exam', 'Description', 30, 50, 'in_progress', $this->adminId);
        
        $student1 = createTestStudent('student1', 'student1@test.com');
        $student2 = createTestStudent('student2', 'student2@test.com');
        
        $result = \Exam::assignToStudents($examId, [$student1, $student2], false);
        
        $this->assertTrue($result);
        
        $assignedStudents = \Exam::getAssignedStudents($examId);
        $this->assertCount(2, $assignedStudents);
    }
    
    /**
     * Test checking if student is assigned to exam
     * Validates: Requirements 5.1 - Display assigned exams
     */
    public function testIsAssignedToStudent() {
        $examId = \Exam::create('Test Exam', 'Description', 30, 50, 'in_progress', $this->adminId);
        
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        $assignment = \Exam::isAssignedToStudent($examId, $this->studentId);
        
        $this->assertNotNull($assignment);
        $this->assertEquals($examId, $assignment['exam_id']);
        $this->assertEquals($this->studentId, $assignment['student_id']);
    }
    
    /**
     * Test getting exams assigned to a student
     * Validates: Requirements 5.1 - Student views assigned exams
     */
    public function testGetAssignedToStudent() {
        $exam1 = \Exam::create('Exam 1', 'Description 1', 30, 50, 'in_progress', $this->adminId);
        $exam2 = \Exam::create('Exam 2', 'Description 2', 45, 75, 'in_progress', $this->adminId);
        
        // Assign questions to make exams available
        $q1 = \Question::create('multiple_choice', 'Q1', 1, $this->adminId, []);
        \Exam::assignQuestions($exam1, [$q1]);
        \Exam::assignQuestions($exam2, [$q1]);
        
        \Exam::assignToStudents($exam1, [$this->studentId], false);
        \Exam::assignToStudents($exam2, [$this->studentId], false);
        
        $assignedExams = \Exam::getAssignedToStudent($this->studentId);
        
        $this->assertCount(2, $assignedExams);
    }
    
    /**
     * Test retake prevention
     * Validates: Requirements 5.3 - Retake prevention
     */
    public function testRetakePrevention() {
        $examId = \Exam::create('Test Exam', 'Description', 30, 50, 'in_progress', $this->adminId);
        
        // Assign exam without retake allowed
        \Exam::assignToStudents($examId, [$this->studentId], false);
        
        // Create a completed session
        $sessionId = \ExamSession::start($examId, $this->studentId);
        \ExamSession::submit($sessionId);
        
        // Check if student can take exam again
        $canTake = \Exam::canStudentTakeExam($examId, $this->studentId);
        
        $this->assertFalse($canTake['allowed']);
        $this->assertStringContainsString('already completed', $canTake['reason']);
    }
    
    /**
     * Test retake allowed
     */
    public function testRetakeAllowed() {
        $examId = \Exam::create('Test Exam', 'Description', 30, 50, 'in_progress', $this->adminId);
        
        // Assign exam with retake allowed
        \Exam::assignToStudents($examId, [$this->studentId], true);
        
        // Create a completed session
        $sessionId = \ExamSession::start($examId, $this->studentId);
        \ExamSession::submit($sessionId);
        
        // Check if student can take exam again
        $canTake = \Exam::canStudentTakeExam($examId, $this->studentId);
        
        $this->assertTrue($canTake['allowed']);
    }
}
