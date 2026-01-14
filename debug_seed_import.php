<?php
/**
 * Debug script to test seed data import
 */

require_once __DIR__ . '/config/database.php';

echo "Testing seed data import...\n\n";

$seedFile = __DIR__ . '/database/seed.sql';
$seed = file_get_contents($seedFile);

echo "Seed file size: " . strlen($seed) . " bytes\n\n";

// Split by semicolon and execute each statement
$statements = explode(';', $seed);

echo "Total statements after split: " . count($statements) . "\n\n";

$validStatements = [];
foreach ($statements as $index => $statement) {
    $trimmed = trim($statement);
    
    // Skip empty statements
    if (empty($trimmed)) {
        continue;
    }
    
    // Skip comment-only statements
    if (preg_match('/^--/', $trimmed)) {
        echo "Skipping comment at index $index\n";
        continue;
    }
    
    // Remove comments from the statement
    $lines = explode("\n", $trimmed);
    $cleanLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !preg_match('/^--/', $line)) {
            $cleanLines[] = $line;
        }
    }
    
    $cleanStatement = implode("\n", $cleanLines);
    
    if (!empty($cleanStatement)) {
        $validStatements[] = $cleanStatement;
        echo "Statement " . (count($validStatements)) . ":\n";
        echo substr($cleanStatement, 0, 100) . "...\n\n";
    }
}

echo "\nTotal valid statements: " . count($validStatements) . "\n\n";

// Try to execute them
try {
    $pdo = getDBConnection();
    
    echo "Executing statements...\n\n";
    
    foreach ($validStatements as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "✓ Statement " . ($index + 1) . " executed successfully\n";
        } catch (PDOException $e) {
            echo "✗ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 200) . "...\n\n";
        }
    }
    
    // Count results
    echo "\n\nResults:\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Users: $userCount\n";
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM questions");
    $questionCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Questions: $questionCount\n";
    
    $result = $pdo->query("SELECT COUNT(*) as count FROM exams");
    $examCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Exams: $examCount\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
