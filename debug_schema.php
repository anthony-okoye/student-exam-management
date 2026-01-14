<?php
require 'config/database.php';

try {
    $pdo = getDBConnection();
    echo "Connected to database: " . DB_NAME . "\n\n";
    
    $schema = file_get_contents('database/schema.sql');
    
    // Split by semicolon
    $statements = explode(';', $schema);
    
    echo "Total statements found: " . count($statements) . "\n\n";
    
    $executed = 0;
    foreach ($statements as $i => $statement) {
        $statement = trim($statement);
        
        // Skip empty or comment-only statements
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }
        
        echo "Executing statement " . ($i + 1) . "...\n";
        echo substr($statement, 0, 100) . "...\n";
        
        try {
            $pdo->exec($statement);
            $executed++;
            echo "✓ Success\n\n";
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "Executed $executed statements successfully\n\n";
    
    // Check tables
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
