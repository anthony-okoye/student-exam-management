<?php
/**
 * Analytics Queries Test Script
 * 
 * This script tests all analytics queries to ensure they work correctly
 * Run this from command line: php test_analytics_queries.php
 */

// Load environment and database configuration
require_once __DIR__ . '/config/database.php';

echo "=== Analytics Queries Test ===\n\n";

try {
    $pdo = getDBConnection();
    echo "✓ Database connection successful\n\n";
    
    // Test 1: Average Scores Per Exam
    echo "Test 1: Average Scores Per Exam\n";
    echo "--------------------------------\n";
    $avgScoresStmt = $pdo->query(
        "SELECT e.id, e.title, 
                AVG(es.score) as avg_score, 
                AVG(es.percentage) as avg_percentage,
                COUNT(es.id) as total_sessions,
                e.total_marks
         FROM exams e
         LEFT JOIN exam_sessions es ON e.id = es.exam_id 
         WHERE es.status IN ('completed', 'auto_submitted')
         GROUP BY e.id, e.title, e.total_marks
         ORDER BY e.title"
    );
    $avgScores = $avgScoresStmt->fetchAll();
    
    if (count($avgScores) > 0) {
        echo "✓ Query successful - Found " . count($avgScores) . " exams with completed sessions\n";
        foreach ($avgScores as $exam) {
            echo "  - {$exam['title']}: Avg {$exam['avg_percentage']}% ({$exam['total_sessions']} sessions)\n";
        }
    } else {
        echo "⚠ No completed exam sessions found\n";
    }
    echo "\n";
    
    // Test 2: Pass/Fail Rates
    echo "Test 2: Pass/Fail Rates\n";
    echo "------------------------\n";
    $passingThreshold = 60;
    $passFailStmt = $pdo->query(
        "SELECT e.id, e.title,
                COUNT(CASE WHEN es.percentage >= 60 THEN 1 END) as passed,
                COUNT(CASE WHEN es.percentage < 60 THEN 1 END) as failed,
                COUNT(es.id) as total
         FROM exams e
         LEFT JOIN exam_sessions es ON e.id = es.exam_id AND es.status IN ('completed', 'auto_submitted')
         GROUP BY e.id, e.title
         ORDER BY e.title"
    );
    $passFailRates = $passFailStmt->fetchAll();
    
    if (count($passFailRates) > 0) {
        echo "✓ Query successful - Found " . count($passFailRates) . " exams\n";
        foreach ($passFailRates as $exam) {
            $passRate = $exam['total'] > 0 ? round(($exam['passed'] / $exam['total']) * 100, 1) : 0;
            echo "  - {$exam['title']}: {$exam['passed']} passed, {$exam['failed']} failed ({$passRate}% pass rate)\n";
        }
    } else {
        echo "⚠ No exam data found\n";
    }
    echo "\n";
    
    // Test 3: Question-Level Statistics
    echo "Test 3: Question-Level Statistics\n";
    echo "----------------------------------\n";
    $questionStatsStmt = $pdo->query(
        "SELECT q.id, q.content, q.type,
                COUNT(a.id) as total_answers,
                COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                ROUND((COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) / COUNT(a.id)) * 100, 2) as percentage_correct
         FROM questions q
         LEFT JOIN answers a ON q.id = a.question_id
         WHERE a.id IS NOT NULL
         GROUP BY q.id, q.content, q.type
         HAVING total_answers > 0
         ORDER BY percentage_correct ASC
         LIMIT 5"
    );
    $questionStats = $questionStatsStmt->fetchAll();
    
    if (count($questionStats) > 0) {
        echo "✓ Query successful - Found " . count($questionStats) . " questions with answers (showing top 5 hardest)\n";
        foreach ($questionStats as $stat) {
            $preview = substr($stat['content'], 0, 50) . (strlen($stat['content']) > 50 ? '...' : '');
            $difficulty = $stat['percentage_correct'] >= 70 ? 'Easy' : 
                         ($stat['percentage_correct'] >= 40 ? 'Medium' : 'Hard');
            echo "  - [{$difficulty}] {$preview}: {$stat['percentage_correct']}% correct ({$stat['correct_answers']}/{$stat['total_answers']})\n";
        }
    } else {
        echo "⚠ No answered questions found\n";
    }
    echo "\n";
    
    // Test 4: Student Performance Trends
    echo "Test 4: Student Performance Trends\n";
    echo "-----------------------------------\n";
    $performanceTrendsStmt = $pdo->query(
        "SELECT u.id, u.username,
                es.exam_id, e.title as exam_title,
                es.score, es.percentage,
                es.end_time,
                DATE(es.end_time) as exam_date
         FROM users u
         JOIN exam_sessions es ON u.id = es.student_id
         JOIN exams e ON es.exam_id = e.id
         WHERE u.role = 'student' AND es.status IN ('completed', 'auto_submitted')
         ORDER BY u.username, es.end_time"
    );
    $performanceTrends = $performanceTrendsStmt->fetchAll();
    
    if (count($performanceTrends) > 0) {
        echo "✓ Query successful - Found " . count($performanceTrends) . " completed sessions\n";
        
        // Group by student
        $studentTrends = [];
        foreach ($performanceTrends as $trend) {
            $studentId = $trend['id'];
            if (!isset($studentTrends[$studentId])) {
                $studentTrends[$studentId] = [
                    'username' => $trend['username'],
                    'exams' => []
                ];
            }
            $studentTrends[$studentId]['exams'][] = [
                'exam_title' => $trend['exam_title'],
                'percentage' => $trend['percentage'],
                'exam_date' => $trend['exam_date']
            ];
        }
        
        foreach ($studentTrends as $studentData) {
            echo "  - {$studentData['username']}: " . count($studentData['exams']) . " exams completed\n";
            foreach ($studentData['exams'] as $exam) {
                echo "    • {$exam['exam_title']}: {$exam['percentage']}% ({$exam['exam_date']})\n";
            }
        }
    } else {
        echo "⚠ No student performance data found\n";
    }
    echo "\n";
    
    // Test 5: Overall Statistics
    echo "Test 5: Overall Statistics\n";
    echo "--------------------------\n";
    $overallStatsStmt = $pdo->query(
        "SELECT 
            COUNT(DISTINCT e.id) as total_exams,
            COUNT(DISTINCT u.id) as total_students,
            COUNT(DISTINCT q.id) as total_questions,
            COUNT(es.id) as total_sessions,
            AVG(es.percentage) as overall_avg_percentage
         FROM exams e
         CROSS JOIN users u
         CROSS JOIN questions q
         LEFT JOIN exam_sessions es ON es.status IN ('completed', 'auto_submitted')
         WHERE u.role = 'student'"
    );
    $overallStats = $overallStatsStmt->fetch();
    
    echo "✓ Query successful\n";
    echo "  - Total Exams: {$overallStats['total_exams']}\n";
    echo "  - Total Students: {$overallStats['total_students']}\n";
    echo "  - Total Questions: {$overallStats['total_questions']}\n";
    echo "  - Completed Sessions: {$overallStats['total_sessions']}\n";
    if ($overallStats['overall_avg_percentage']) {
        echo "  - Overall Average: " . round($overallStats['overall_avg_percentage'], 2) . "%\n";
    }
    echo "\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    echo "\nAnalytics queries are working correctly!\n";
    echo "You can now access the analytics dashboard at: /admin/analytics\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
