<?php
/**
 * Exam Model
 * 
 * Handles exam data operations including CRUD for exams and question assignments
 */

require_once BASE_PATH . '/config/database.php';

class Exam {
    private $id;
    private $title;
    private $description;
    private $duration;
    private $totalMarks;
    private $status;
    private $createdBy;
    private $createdAt;
    private $updatedAt;
    
    /**
     * Create a new exam
     * 
     * @param string $title Exam title
     * @param string $description Exam description
     * @param int $duration Duration in minutes
     * @param int $totalMarks Total marks for the exam
     * @param string $status Exam status (draft, in_progress, closed)
     * @param int $createdBy User ID of creator
     * @return int|false Exam ID if successful, false otherwise
     */
    public static function create($title, $description, $duration, $totalMarks, $status, $createdBy) {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare(
                "INSERT INTO exams (title, description, duration, total_marks, status, created_by) 
                 VALUES (:title, :description, :duration, :total_marks, :status, :created_by)"
            );
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'duration' => $duration,
                'total_marks' => $totalMarks,
                'status' => $status,
                'created_by' => $createdBy
            ]);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all exams
     * 
     * @return array Array of exam objects
     */
    public static function getAll() {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query(
                "SELECT e.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
                 FROM exams e 
                 LEFT JOIN users u ON e.created_by = u.id 
                 ORDER BY e.created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching all exams: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get exam by ID
     * 
     * @param int $id Exam ID
     * @return array|null Exam data if found, null otherwise
     */
    public static function getById($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT e.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
                 FROM exams e 
                 LEFT JOIN users u ON e.created_by = u.id 
                 WHERE e.id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching exam by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an exam
     * 
     * @param int $id Exam ID
     * @param string $title Exam title
     * @param string $description Exam description
     * @param int $duration Duration in minutes
     * @param int $totalMarks Total marks for the exam
     * @param string $status Exam status
     * @return bool True if successful, false otherwise
     */
    public static function update($id, $title, $description, $duration, $totalMarks, $status) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "UPDATE exams 
                 SET title = :title, description = :description, duration = :duration, 
                     total_marks = :total_marks, status = :status
                 WHERE id = :id"
            );
            $stmt->execute([
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'duration' => $duration,
                'total_marks' => $totalMarks,
                'status' => $status
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete an exam
     * 
     * @param int $id Exam ID
     * @return bool True if successful, false otherwise
     */
    public static function delete($id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign questions to an exam
     * 
     * @param int $examId Exam ID
     * @param array $questionIds Array of question IDs
     * @return bool True if successful, false otherwise
     */
    public static function assignQuestions($examId, $questionIds) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // First, remove existing question assignments
            $deleteStmt = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id");
            $deleteStmt->execute(['exam_id' => $examId]);
            
            // Then, insert new assignments
            if (!empty($questionIds)) {
                $insertStmt = $pdo->prepare(
                    "INSERT INTO exam_questions (exam_id, question_id, question_order) 
                     VALUES (:exam_id, :question_id, :question_order)"
                );
                
                foreach ($questionIds as $order => $questionId) {
                    $insertStmt->execute([
                        'exam_id' => $examId,
                        'question_id' => $questionId,
                        'question_order' => $order + 1
                    ]);
                }
                
                // Calculate and update exam duration (2 minutes per question)
                $duration = count($questionIds) * 2;
                
                // Calculate total marks
                $marksStmt = $pdo->prepare(
                    "SELECT SUM(q.marks) as total_marks 
                     FROM questions q 
                     WHERE q.id IN (" . implode(',', array_fill(0, count($questionIds), '?')) . ")"
                );
                $marksStmt->execute($questionIds);
                $result = $marksStmt->fetch();
                $totalMarks = $result['total_marks'] ?? 0;
                
                // Update exam with calculated values
                $updateStmt = $pdo->prepare(
                    "UPDATE exams SET duration = :duration, total_marks = :total_marks WHERE id = :exam_id"
                );
                $updateStmt->execute([
                    'duration' => $duration,
                    'total_marks' => $totalMarks,
                    'exam_id' => $examId
                ]);
            }
            
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error assigning questions to exam: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get questions assigned to an exam
     * 
     * @param int $examId Exam ID
     * @return array Array of question IDs
     */
    public static function getAssignedQuestions($examId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT eq.question_id, q.*, eq.question_order
                 FROM exam_questions eq
                 JOIN questions q ON eq.question_id = q.id
                 WHERE eq.exam_id = :exam_id
                 ORDER BY eq.question_order ASC"
            );
            $stmt->execute(['exam_id' => $examId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching assigned questions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all available exams for students
     * For MVP: Returns all exams with status 'in_progress' that have questions assigned
     * 
     * @return array Array of exam objects
     */
    public static function getAllAvailable() {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query(
                "SELECT e.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
                 FROM exams e 
                 LEFT JOIN users u ON e.created_by = u.id 
                 WHERE e.status = 'in_progress'
                 AND (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) > 0
                 ORDER BY e.created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching available exams: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get exams assigned to a specific student
     * 
     * @param int $studentId Student ID
     * @return array Array of exam objects
     */
    public static function getAssignedToStudent($studentId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT e.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
                 ea.retake_allowed, ea.assigned_at
                 FROM exams e 
                 LEFT JOIN users u ON e.created_by = u.id 
                 INNER JOIN exam_assignments ea ON e.id = ea.exam_id
                 WHERE ea.student_id = :student_id
                 AND e.status = 'in_progress'
                 AND (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) > 0
                 ORDER BY ea.assigned_at DESC"
            );
            $stmt->execute(['student_id' => $studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching assigned exams: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Assign exam to students
     * 
     * @param int $examId Exam ID
     * @param array $studentIds Array of student IDs
     * @param bool $retakeAllowed Whether retakes are allowed
     * @return bool True if successful, false otherwise
     */
    public static function assignToStudents($examId, $studentIds, $retakeAllowed = false) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // First, remove existing assignments for this exam
            $deleteStmt = $pdo->prepare("DELETE FROM exam_assignments WHERE exam_id = :exam_id");
            $deleteStmt->execute(['exam_id' => $examId]);
            
            // Then, insert new assignments
            if (!empty($studentIds)) {
                $insertStmt = $pdo->prepare(
                    "INSERT INTO exam_assignments (exam_id, student_id, retake_allowed) 
                     VALUES (:exam_id, :student_id, :retake_allowed)"
                );
                
                foreach ($studentIds as $studentId) {
                    $insertStmt->execute([
                        'exam_id' => $examId,
                        'student_id' => $studentId,
                        'retake_allowed' => $retakeAllowed ? 1 : 0
                    ]);
                }
            }
            
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error assigning exam to students: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get students assigned to an exam
     * 
     * @param int $examId Exam ID
     * @return array Array of student IDs
     */
    public static function getAssignedStudents($examId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT ea.student_id, u.username, u.email, ea.retake_allowed, ea.assigned_at
                 FROM exam_assignments ea
                 JOIN users u ON ea.student_id = u.id
                 WHERE ea.exam_id = :exam_id
                 ORDER BY u.username ASC"
            );
            $stmt->execute(['exam_id' => $examId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching assigned students: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if a student is assigned to an exam
     * 
     * @param int $examId Exam ID
     * @param int $studentId Student ID
     * @return array|null Assignment data if assigned, null otherwise
     */
    public static function isAssignedToStudent($examId, $studentId) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "SELECT * FROM exam_assignments 
                 WHERE exam_id = :exam_id AND student_id = :student_id
                 LIMIT 1"
            );
            $stmt->execute([
                'exam_id' => $examId,
                'student_id' => $studentId
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error checking exam assignment: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if a student can take an exam (considering retake rules)
     * 
     * @param int $examId Exam ID
     * @param int $studentId Student ID
     * @return array Result with 'allowed' boolean and 'reason' string
     */
    public static function canStudentTakeExam($examId, $studentId) {
        try {
            $pdo = getDBConnection();
            
            // Check if exam is assigned to student
            $assignment = self::isAssignedToStudent($examId, $studentId);
            if (!$assignment) {
                return ['allowed' => false, 'reason' => 'Exam not assigned to you'];
            }
            
            // Check if student has already completed the exam
            $sessionStmt = $pdo->prepare(
                "SELECT id, status FROM exam_sessions 
                 WHERE exam_id = :exam_id AND student_id = :student_id
                 AND status IN ('completed', 'auto_submitted')
                 LIMIT 1"
            );
            $sessionStmt->execute([
                'exam_id' => $examId,
                'student_id' => $studentId
            ]);
            $completedSession = $sessionStmt->fetch();
            
            if ($completedSession) {
                // Check if retake is allowed
                if (!$assignment['retake_allowed']) {
                    return ['allowed' => false, 'reason' => 'You have already completed this exam. Retakes are not allowed.'];
                }
            }
            
            // Check if there's an in-progress session
            $inProgressStmt = $pdo->prepare(
                "SELECT id FROM exam_sessions 
                 WHERE exam_id = :exam_id AND student_id = :student_id
                 AND status = 'in_progress'
                 LIMIT 1"
            );
            $inProgressStmt->execute([
                'exam_id' => $examId,
                'student_id' => $studentId
            ]);
            $inProgressSession = $inProgressStmt->fetch();
            
            if ($inProgressSession) {
                return ['allowed' => true, 'reason' => 'Continue your exam', 'session_id' => $inProgressSession['id']];
            }
            
            return ['allowed' => true, 'reason' => 'You can take this exam'];
        } catch (PDOException $e) {
            error_log("Error checking if student can take exam: " . $e->getMessage());
            return ['allowed' => false, 'reason' => 'Error checking exam eligibility'];
        }
    }
}
