<?php
require 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "Reading seed file...\n";
    $seed = file_get_contents('database/seed_postgresql.sql');
    
    echo "Executing seed...\n";
    $pdo->exec($seed);
    
    echo "Seed executed successfully!\n\n";
    
    // Check counts
    $result = $pdo->query('SELECT COUNT(*) FROM users');
    echo 'Users: ' . $result->fetchColumn() . "\n";
    
    $result = $pdo->query('SELECT COUNT(*) FROM questions');
    echo 'Questions: ' . $result->fetchColumn() . "\n";
    
    $result = $pdo->query('SELECT COUNT(*) FROM exams');
    echo 'Exams: ' . $result->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'Trace: ' . $e->getTraceAsString() . "\n";
}
