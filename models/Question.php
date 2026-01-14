<?php
/**
 * Question Model
 * 
 * Handles question data operations including CRUD for questions and options
 */

require_once BASE_PATH . '/config/database.php';

class Question {
    private $id;
    private $type;
    private $content;
    private $marks;
    private $createdBy;
    private $createdAt;
    private $updatedAt;
    private $options = [];
    
    /**
     * Create a new question
     * 
     * @param string $type Question type (multiple_choice, true_false, fill_blank)
     * @param string $content Question content/text
     * @param int $marks Marks for the question
     * @param int $createdBy User ID of creator
     * @param array $options Array of options (for choice-based questions)
     * @return int|false Question ID if successful, false otherwise
     */
    public static function create($type, $content, $marks, $createdBy, $options = []) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // Insert question
            $stmt = $pdo->prepare(
                "INSERT INTO questions (type, content, marks, created_by) 
                 VALUES (:type, :content, :marks, :created_by)"
            );
            $stmt->execute([
                'type' => $type,
                'content' => $content,
                'marks' => $marks,
                'created_by' => $createdBy
            ]);
            
            $questionId = $pdo->lastInsertId();
            
            // Insert options if provided
            if (!empty($options)) {
                $optionStmt = $pdo->prepare(
                    "INSERT INTO question_options (question_id, option_text, is_correct, option_order) 
                     VALUES (:question_id, :option_text, :is_correct, :option_order)"
                );
                
                foreach ($options as $index => $option) {
                    $optionStmt->execute([
                        'question_id' => $questionId,
                        'option_text' => $option['text'],
                        'is_correct' => $option['is_correct'] ? 1 : 0,
                        'option_order' => $index + 1
                    ]);
                }
            }
            
            $pdo->commit();
            return $questionId;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creating question: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all questions
     * 
     * @return array Array of question objects with their options
     */
    public static function getAll() {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query(
                "SELECT q.*, u.username as creator_name 
                 FROM questions q 
                 LEFT JOIN users u ON q.created_by = u.id 
                 ORDER BY q.created_at DESC"
            );
            $questions = $stmt->fetchAll();
            
            // Fetch options for each question
            foreach ($questions as &$question) {
                $question['options'] = self::getOptionsByQuestionId($question['id']);
            }
            
            return $questions;
        } catch (PDOException $e) {
            error_log("Error fetching all questions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get question by ID
     * 
     * @param int $id Question ID
     * @return array|null Question data with options if found, null otherwise
     */
    public static function getById($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT q.*, u.username as creator_name 
                 FROM questions q 
                 LEFT JOIN users u ON q.created_by = u.id 
                 WHERE q.id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);
            $question = $stmt->fetch();
            
            if (!$question) {
                return null;
            }
            
            // Fetch options for the question
            $question['options'] = self::getOptionsByQuestionId($question['id']);
            
            return $question;
        } catch (PDOException $e) {
            error_log("Error fetching question by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete a question
     * 
     * @param int $id Question ID
     * @return bool True if successful, false otherwise
     */
    public static function delete($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting question: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get options for a question
     * 
     * @param int $questionId Question ID
     * @return array Array of options
     */
    private static function getOptionsByQuestionId($questionId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT * FROM question_options 
                 WHERE question_id = :question_id 
                 ORDER BY option_order ASC"
            );
            $stmt->execute(['question_id' => $questionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching question options: " . $e->getMessage());
            return [];
        }
    }
}
