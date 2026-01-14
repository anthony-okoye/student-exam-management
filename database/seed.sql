-- Seed data for Online Examination System
-- This file populates the database with initial test data

-- Note: Database selection removed - installer handles this

-- Insert admin user (username: admin, password: admin123)
-- Password hashed using bcrypt with cost 12
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@example.com', '$2y$12$4V9PLJ0SQyxERTTRigwGieFs45VQwYQcm5ZdOvhtg9bp7QqLoSRMe', 'admin');

-- Insert test students (username: student1/student2, password: pass123)
-- Password hashed using bcrypt with cost 12
INSERT INTO users (username, email, password_hash, role) VALUES
('student1', 'student1@example.com', '$2y$12$v/xgWAAqGxtchMCQXOoBD.aWLQ5VSvCdXAxLj3IpSbfBfeXcgdl7u', 'student'),
('student2', 'student2@example.com', '$2y$12$v/xgWAAqGxtchMCQXOoBD.aWLQ5VSvCdXAxLj3IpSbfBfeXcgdl7u', 'student');

-- Insert sample questions
-- Multiple Choice Questions
INSERT INTO questions (type, content, marks, created_by) VALUES
('multiple_choice', 'What is 2 + 2?', 1, 1),
('multiple_choice', 'Which programming language is this system built with?', 1, 1),
('multiple_choice', 'What does MVC stand for?', 1, 1);

-- Insert options for multiple choice questions
-- Question 1: What is 2 + 2?
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(1, '3', FALSE, 1),
(1, '4', TRUE, 2),
(1, '5', FALSE, 3),
(1, '6', FALSE, 4);

-- Question 2: Which programming language is this system built with?
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(2, 'Python', FALSE, 1),
(2, 'PHP', TRUE, 2),
(2, 'Java', FALSE, 3),
(2, 'Ruby', FALSE, 4);

-- Question 3: What does MVC stand for?
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(3, 'Model View Controller', TRUE, 1),
(3, 'Multiple View Container', FALSE, 2),
(3, 'Main Visual Component', FALSE, 3),
(3, 'Model Verification Code', FALSE, 4);

-- True/False Questions
INSERT INTO questions (type, content, marks, created_by) VALUES
('true_false', 'PHP is a server-side programming language.', 1, 1),
('true_false', 'MySQL is a NoSQL database.', 1, 1),
('true_false', 'Bootstrap is a CSS framework.', 1, 1);

-- Insert options for true/false questions
-- Question 4: PHP is a server-side programming language
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(4, 'True', TRUE, 1),
(4, 'False', FALSE, 2);

-- Question 5: MySQL is a NoSQL database
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(5, 'True', FALSE, 1),
(5, 'False', TRUE, 2);

-- Question 6: Bootstrap is a CSS framework
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(6, 'True', TRUE, 1),
(6, 'False', FALSE, 2);

-- Fill in the Blank Questions
INSERT INTO questions (type, content, marks, created_by) VALUES
('fill_blank', 'The capital of France is ____.', 1, 1),
('fill_blank', 'HTML stands for HyperText ____ Language.', 1, 1),
('fill_blank', 'The default port for HTTP is ____.', 1, 1);

-- Insert correct answers for fill in the blank (stored as options)
-- Question 7: The capital of France
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(7, 'Paris', TRUE, 1);

-- Question 8: HTML stands for
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(8, 'Markup', TRUE, 1);

-- Question 9: Default HTTP port
INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES
(9, '80', TRUE, 1);

-- Insert sample exams
INSERT INTO exams (title, description, duration, total_marks, status, created_by) VALUES
('Sample Programming Quiz', 'A quick quiz to test basic programming knowledge', 6, 3, 'in_progress', 1),
('Web Development Basics', 'Test your knowledge of web development fundamentals', 12, 6, 'in_progress', 1),
('Complete Assessment', 'Comprehensive test covering all question types', 18, 9, 'in_progress', 1);

-- Assign questions to exams
-- Exam 1: Sample Programming Quiz (3 questions - one of each type)
INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES
(1, 1, 1),  -- Multiple choice: What is 2 + 2?
(1, 4, 2),  -- True/False: PHP is server-side
(1, 7, 3);  -- Fill blank: Capital of France

-- Exam 2: Web Development Basics (6 questions)
INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES
(2, 2, 1),  -- Multiple choice: Programming language
(2, 3, 2),  -- Multiple choice: MVC
(2, 5, 3),  -- True/False: MySQL NoSQL
(2, 6, 4),  -- True/False: Bootstrap
(2, 8, 5),  -- Fill blank: HTML
(2, 9, 6);  -- Fill blank: HTTP port

-- Exam 3: Complete Assessment (all 9 questions)
INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES
(3, 1, 1),
(3, 2, 2),
(3, 3, 3),
(3, 4, 4),
(3, 5, 5),
(3, 6, 6),
(3, 7, 7),
(3, 8, 8),
(3, 9, 9);
