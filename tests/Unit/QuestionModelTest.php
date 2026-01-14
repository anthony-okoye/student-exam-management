<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Question Model
 * 
 * Tests question CRUD operations and question type validation
 */
class QuestionModelTest extends TestCase {
    
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
     * Test creating a multiple choice question
     * Validates: Requirements 2.1, 2.5 - Create question with multiple choice type
     */
    public function testCreateMultipleChoiceQuestion() {
        $options = [
            ['text' => 'Option A', 'is_correct' => false],
            ['text' => 'Option B', 'is_correct' => true],
            ['text' => 'Option C', 'is_correct' => false],
            ['text' => 'Option D', 'is_correct' => false]
        ];
        
        $questionId = \Question::create(
            'multiple_choice',
            'What is 2 + 2?',
            1,
            $this->adminId,
            $options
        );
        
        $this->assertIsNumeric($questionId);
        $this->assertGreaterThan(0, (int)$questionId);
        
        // Verify question was created with options
        $question = \Question::getById($questionId);
        $this->assertNotNull($question);
        $this->assertEquals('multiple_choice', $question['type']);
        $this->assertEquals('What is 2 + 2?', $question['content']);
        $this->assertCount(4, $question['options']);
        
        // Verify exactly one correct answer
        $correctCount = 0;
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctCount++;
            }
        }
        $this->assertEquals(1, $correctCount);
    }
    
    /**
     * Test creating a true/false question
     * Validates: Requirements 2.7 - True/false question structure
     */
    public function testCreateTrueFalseQuestion() {
        $options = [
            ['text' => 'True', 'is_correct' => true],
            ['text' => 'False', 'is_correct' => false]
        ];
        
        $questionId = \Question::create(
            'true_false',
            'The sky is blue.',
            1,
            $this->adminId,
            $options
        );
        
        $this->assertNotFalse($questionId);
        
        $question = \Question::getById($questionId);
        $this->assertEquals('true_false', $question['type']);
        $this->assertCount(2, $question['options']);
        
        // Verify exactly one correct answer
        $correctCount = 0;
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctCount++;
            }
        }
        $this->assertEquals(1, $correctCount);
    }
    
    /**
     * Test creating a fill in the blank question
     * Validates: Requirements 2.8 - Fill in blank storage
     */
    public function testCreateFillBlankQuestion() {
        $options = [
            ['text' => 'Paris', 'is_correct' => true]
        ];
        
        $questionId = \Question::create(
            'fill_blank',
            'The capital of France is ____.',
            2,
            $this->adminId,
            $options
        );
        
        $this->assertNotFalse($questionId);
        
        $question = \Question::getById($questionId);
        $this->assertEquals('fill_blank', $question['type']);
        $this->assertEquals(2, $question['marks']);
        $this->assertCount(1, $question['options']);
        $this->assertEquals('Paris', $question['options'][0]['option_text']);
        $this->assertTrue((bool)$question['options'][0]['is_correct']);
    }
    
    /**
     * Test creating a select all that apply question
     * Validates: Requirements 2.6 - Select all validation
     */
    public function testCreateSelectAllQuestion() {
        $options = [
            ['text' => 'Red', 'is_correct' => true],
            ['text' => 'Blue', 'is_correct' => true],
            ['text' => 'Green', 'is_correct' => false],
            ['text' => 'Yellow', 'is_correct' => true]
        ];
        
        $questionId = \Question::create(
            'select_all',
            'Select all primary colors:',
            3,
            $this->adminId,
            $options
        );
        
        $this->assertNotFalse($questionId);
        
        $question = \Question::getById($questionId);
        $this->assertEquals('select_all', $question['type']);
        $this->assertCount(4, $question['options']);
        
        // Verify at least one correct answer
        $correctCount = 0;
        foreach ($question['options'] as $option) {
            if ($option['is_correct']) {
                $correctCount++;
            }
        }
        $this->assertGreaterThanOrEqual(1, $correctCount);
    }
    
    /**
     * Test creating a short answer question
     * Validates: Requirements 2.9 - Short answer storage
     */
    public function testCreateShortAnswerQuestion() {
        $options = [
            ['text' => 'Photosynthesis is the process by which plants convert light energy into chemical energy', 'is_correct' => true]
        ];
        
        $questionId = \Question::create(
            'short_answer',
            'Explain photosynthesis.',
            5,
            $this->adminId,
            $options
        );
        
        $this->assertNotFalse($questionId);
        
        $question = \Question::getById($questionId);
        $this->assertEquals('short_answer', $question['type']);
        $this->assertEquals(5, $question['marks']);
    }
    
    /**
     * Test getting all questions
     */
    public function testGetAllQuestions() {
        \Question::create('multiple_choice', 'Question 1', 1, $this->adminId, []);
        \Question::create('true_false', 'Question 2', 1, $this->adminId, []);
        \Question::create('fill_blank', 'Question 3', 2, $this->adminId, []);
        
        $questions = \Question::getAll();
        
        $this->assertIsArray($questions);
        $this->assertCount(3, $questions);
    }
    
    /**
     * Test getting question by ID
     */
    public function testGetQuestionById() {
        $questionId = \Question::create(
            'multiple_choice',
            'Test question',
            1,
            $this->adminId,
            []
        );
        
        $question = \Question::getById($questionId);
        
        $this->assertNotNull($question);
        $this->assertEquals($questionId, $question['id']);
        $this->assertEquals('Test question', $question['content']);
    }
    
    /**
     * Test getting non-existent question returns null
     */
    public function testGetNonExistentQuestionReturnsNull() {
        $question = \Question::getById(99999);
        
        $this->assertNull($question);
    }
    
    /**
     * Test deleting a question
     * Validates: Requirements 2.3 - Admin deletes question
     */
    public function testDeleteQuestion() {
        $questionId = \Question::create(
            'multiple_choice',
            'Delete me',
            1,
            $this->adminId,
            []
        );
        
        $result = \Question::delete($questionId);
        
        $this->assertTrue($result);
        
        $question = \Question::getById($questionId);
        $this->assertNull($question);
    }
    
    /**
     * Test question options are ordered correctly
     */
    public function testQuestionOptionsAreOrdered() {
        $options = [
            ['text' => 'First', 'is_correct' => false],
            ['text' => 'Second', 'is_correct' => false],
            ['text' => 'Third', 'is_correct' => true],
            ['text' => 'Fourth', 'is_correct' => false]
        ];
        
        $questionId = \Question::create(
            'multiple_choice',
            'Order test',
            1,
            $this->adminId,
            $options
        );
        
        $question = \Question::getById($questionId);
        
        $this->assertEquals('First', $question['options'][0]['option_text']);
        $this->assertEquals('Second', $question['options'][1]['option_text']);
        $this->assertEquals('Third', $question['options'][2]['option_text']);
        $this->assertEquals('Fourth', $question['options'][3]['option_text']);
    }
}
