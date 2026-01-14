<?php
/**
 * ExamSession Model
 * 
 * Handles exam session operations including starting exams, saving answers, and submitting
 */

require_once BASE_PATH . '/config/database.php';

class ExamSession {
    private $id;
    private $examId;
    private $studentId;
    private $startTime;
    private $endTime;
    private $status;
    private $score;
    private $percentage;
    private $tabSwitchCount;
    
    /**
     * Start a new exam session
     * 
     * @param int $examId Exam ID
     * @param int $studentId Student ID
     * @return int|false Session ID if successful, false otherwise
     */
    public static function start($examId, $studentId) {
        try {
            $pdo = getDBConnection();
            
            // Check if student already has a completed session for this exam
            $checkStmt = $pdo->prepare(
                "SELECT id, status FROM exam_sessions 
                 WHERE exam_id = :exam_id AND student_id = :student_id 
                 ORDER BY created_at DESC LIMIT 1"
            );
            $checkStmt->execute([
                'exam_id' => $examId,
                'student_id' => $studentId
            ]);
            $existingSession = $checkStmt->fetch();
            
            // For MVP: Prevent retaking if already completed
            if ($existingSession && $existingSession['status'] === 'completed') {
                return false;
            }
            
            // If there's an in-progress session, return that session ID
            if ($existingSession && $existingSession['status'] === 'in_progress') {
                return $existingSession['id'];
            }
            
            // Create new session
            $stmt = $pdo->prepare(
                "INSERT INTO exam_sessions (exam_id, student_id, start_time, status) 
                 VALUES (:exam_id, :student_id, NOW(), 'in_progress')"
            );
            $stmt->execute([
                'exam_id' => $examId,
                'student_id' => $studentId
            ]);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error starting exam session: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save or update an answer for a question
     * 
     * @param int $sessionId Session ID
     * @param int $questionId Question ID
     * @param mixed $answer Answer data (string or array)
     * @return bool|string True if successful, 'time_expired' if time expired, false otherwise
     */
    public static function saveAnswer($sessionId, $questionId, $answer) {
        try {
            $pdo = getDBConnection();
            
            // Check if session is still in progress
            $sessionStmt = $pdo->prepare("SELECT status FROM exam_sessions WHERE id = :id");
            $sessionStmt->execute(['id' => $sessionId]);
            $session = $sessionStmt->fetch();
            
            if (!$session || $session['status'] !== 'in_progress') {
                return false;
            }
            
            // Validate timer - check if time has expired
            $remainingTime = self::getRemainingTime($sessionId);
            if ($remainingTime === false || $remainingTime <= 0) {
                // Time expired - auto-submit the exam
                self::autoSubmit($sessionId);
                return 'time_expired';
            }
            
            // Prepare answer data based on type
            $answerText = null;
            $selectedOptions = null;
            
            if (is_array($answer)) {
                // For multiple choice or select all - store as JSON
                $selectedOptions = json_encode($answer);
            } else {
                // For text-based answers
                $answerText = $answer;
            }
            
            // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior
            $stmt = $pdo->prepare(
                "INSERT INTO answers (session_id, question_id, answer_text, selected_options, answered_at) 
                 VALUES (:session_id, :question_id, :answer_text, :selected_options, NOW())
                 ON DUPLICATE KEY UPDATE 
                 answer_text = VALUES(answer_text), 
                 selected_options = VALUES(selected_options),
                 updated_at = NOW()"
            );
            
            $stmt->execute([
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'answer_text' => $answerText,
                'selected_options' => $selectedOptions
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error saving answer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Submit exam and finalize session
     * 
     * @param int $sessionId Session ID
     * @return bool True if successful, false otherwise
     */
    public static function submit($sessionId) {
        try {
            $pdo = getDBConnection();
            
            // Update session status to completed
            $stmt = $pdo->prepare(
                "UPDATE exam_sessions 
                 SET status = 'completed', end_time = NOW() 
                 WHERE id = :id AND status = 'in_progress'"
            );
            $stmt->execute(['id' => $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            // Calculate score
            self::calculateScore($sessionId);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error submitting exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Auto-submit exam when timer expires
     * 
     * @param int $sessionId Session ID
     * @return bool True if successful, false otherwise
     */
    public static function autoSubmit($sessionId) {
        try {
            $pdo = getDBConnection();
            
            // Update session status to auto_submitted
            $stmt = $pdo->prepare(
                "UPDATE exam_sessions 
                 SET status = 'auto_submitted', end_time = NOW() 
                 WHERE id = :id AND status = 'in_progress'"
            );
            $stmt->execute(['id' => $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            // Calculate score
            self::calculateScore($sessionId);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error auto-submitting exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all answers for a session
     * 
     * @param int $sessionId Session ID
     * @return array Array of answers
     */
    public static function getAnswers($sessionId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT a.*, q.type as question_type, q.content as question_content, q.marks
                 FROM answers a
                 JOIN questions q ON a.question_id = q.id
                 WHERE a.session_id = :session_id"
            );
            $stmt->execute(['session_id' => $sessionId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching answers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get session by ID
     * 
     * @param int $sessionId Session ID
     * @return array|null Session data if found, null otherwise
     */
    public static function getById($sessionId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT es.*, e.title as exam_title, e.duration, e.total_marks as exam_total_marks
                 FROM exam_sessions es
                 JOIN exams e ON es.exam_id = e.id
                 WHERE es.id = :id"
            );
            $stmt->execute(['id' => $sessionId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching session: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate score for a completed exam session
     * 
     * @param int $sessionId Session ID
     * @return bool True if successful, false otherwise
     */
    private static function calculateScore($sessionId) {
        try {
            $pdo = getDBConnection();
            
            // Get all answers for this session
            $answersStmt = $pdo->prepare(
                "SELECT a.*, q.type, q.marks
                 FROM answers a
                 JOIN questions q ON a.question_id = q.id
                 WHERE a.session_id = :session_id"
            );
            $answersStmt->execute(['session_id' => $sessionId]);
            $answers = $answersStmt->fetchAll();
            
            $totalScore = 0;
            
            foreach ($answers as $answer) {
                $marksAwarded = 0;
                $isCorrect = false;
                
                // Get correct answer(s) based on question type
                switch ($answer['type']) {
                    case 'multiple_choice':
                    case 'true_false':
                        // Get the correct option ID
                        $correctStmt = $pdo->prepare(
                            "SELECT id FROM question_options 
                             WHERE question_id = :question_id AND is_correct = 1"
                        );
                        $correctStmt->execute(['question_id' => $answer['question_id']]);
                        $correctOption = $correctStmt->fetch();
                        
                        if ($correctOption && $answer['selected_options']) {
                            $selectedOptions = json_decode($answer['selected_options'], true);
                            if (is_array($selectedOptions) && count($selectedOptions) === 1 && 
                                $selectedOptions[0] == $correctOption['id']) {
                                $isCorrect = true;
                                $marksAwarded = $answer['marks'];
                            }
                        }
                        break;
                    
                    case 'select_all':
                        // Get all correct option IDs
                        $correctStmt = $pdo->prepare(
                            "SELECT id FROM question_options 
                             WHERE question_id = :question_id AND is_correct = 1"
                        );
                        $correctStmt->execute(['question_id' => $answer['question_id']]);
                        $correctOptions = $correctStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if ($answer['selected_options']) {
                            $selectedOptions = json_decode($answer['selected_options'], true);
                            sort($correctOptions);
                            sort($selectedOptions);
                            
                            if ($correctOptions === $selectedOptions) {
                                $isCorrect = true;
                                $marksAwarded = $answer['marks'];
                            }
                        }
                        break;
                    
                    case 'fill_blank':
                        // Get correct answer text
                        $correctStmt = $pdo->prepare(
                            "SELECT option_text FROM question_options 
                             WHERE question_id = :question_id AND is_correct = 1 LIMIT 1"
                        );
                        $correctStmt->execute(['question_id' => $answer['question_id']]);
                        $correctAnswer = $correctStmt->fetch();
                        
                        if ($correctAnswer && $answer['answer_text']) {
                            // Case-insensitive comparison with trimmed whitespace
                            if (strcasecmp(trim($answer['answer_text']), trim($correctAnswer['option_text'])) === 0) {
                                $isCorrect = true;
                                $marksAwarded = $answer['marks'];
                            }
                        }
                        break;
                    
                    case 'short_answer':
                        // For MVP: Store for manual review, no auto-scoring
                        // In production, could implement keyword matching
                        break;
                }
                
                // Update answer with scoring results
                $updateAnswerStmt = $pdo->prepare(
                    "UPDATE answers 
                     SET is_correct = :is_correct, marks_awarded = :marks_awarded 
                     WHERE id = :id"
                );
                $updateAnswerStmt->execute([
                    'is_correct' => $isCorrect ? 1 : 0,
                    'marks_awarded' => $marksAwarded,
                    'id' => $answer['id']
                ]);
                
                $totalScore += $marksAwarded;
            }
            
            // Get exam total marks
            $examStmt = $pdo->prepare(
                "SELECT e.total_marks 
                 FROM exam_sessions es
                 JOIN exams e ON es.exam_id = e.id
                 WHERE es.id = :session_id"
            );
            $examStmt->execute(['session_id' => $sessionId]);
            $exam = $examStmt->fetch();
            
            $totalMarks = $exam['total_marks'] ?? 1; // Avoid division by zero
            $percentage = ($totalScore / $totalMarks) * 100;
            
            // Update session with score
            $updateSessionStmt = $pdo->prepare(
                "UPDATE exam_sessions 
                 SET score = :score, percentage = :percentage 
                 WHERE id = :id"
            );
            $updateSessionStmt->execute([
                'score' => $totalScore,
                'percentage' => round($percentage, 2),
                'id' => $sessionId
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error calculating score: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remaining time for a session in seconds
     * 
     * @param int $sessionId Session ID
     * @return int|false Remaining time in seconds, or false if error
     */
    public static function getRemainingTime($sessionId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT es.start_time, e.duration 
                 FROM exam_sessions es
                 JOIN exams e ON es.exam_id = e.id
                 WHERE es.id = :id AND es.status = 'in_progress'"
            );
            $stmt->execute(['id' => $sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return false;
            }
            
            $startTime = strtotime($session['start_time']);
            $durationSeconds = $session['duration'] * 60; // Convert minutes to seconds
            $currentTime = time();
            $elapsedSeconds = $currentTime - $startTime;
            $remainingSeconds = $durationSeconds - $elapsedSeconds;
            
            return max(0, $remainingSeconds);
        } catch (PDOException $e) {
            error_log("Error getting remaining time: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Increment tab switch count for anti-cheating
     * 
     * @param int $sessionId Session ID
     * @return int|false New tab switch count, or false if error
     */
    public static function incrementTabSwitchCount($sessionId) {
        try {
            $pdo = getDBConnection();
            
            // Increment the count
            $stmt = $pdo->prepare(
                "UPDATE exam_sessions 
                 SET tab_switch_count = tab_switch_count + 1 
                 WHERE id = :id AND status = 'in_progress'"
            );
            $stmt->execute(['id' => $sessionId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            // Get the new count
            $getStmt = $pdo->prepare(
                "SELECT tab_switch_count FROM exam_sessions WHERE id = :id"
            );
            $getStmt->execute(['id' => $sessionId]);
            $result = $getStmt->fetch();
            
            return $result ? $result['tab_switch_count'] : false;
        } catch (PDOException $e) {
            error_log("Error incrementing tab switch count: " . $e->getMessage());
            return false;
        }
    }
}
