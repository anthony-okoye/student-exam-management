<?php
echo "Testing database connection...\n\n";

// Load .env
require 'config/database.php';

echo "Configuration:\n";
echo "  DB_CONNECTION: " . DB_CONNECTION . "\n";
echo "  DB_HOST: " . DB_HOST . "\n";
echo "  DB_PORT: " . DB_PORT . "\n";
echo "  DB_NAME: " . DB_NAME . "\n";
echo "  DB_USER: " . DB_USER . "\n";
echo "  DB_PASS: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : '(empty)') . "\n\n";

try {
    echo "Attempting connection...\n";
    $pdo = getDBConnection();
    echo "✓ Connection successful!\n\n";
    
    // Test query
    $result = $pdo->query("SELECT DATABASE() as current_db, VERSION() as version");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "Connected to:\n";
    echo "  Database: " . ($row['current_db'] ?? 'none') . "\n";
    echo "  MySQL Version: " . $row['version'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Check if MySQL is running\n";
    echo "2. Verify credentials in .env file\n";
    echo "3. Check if database '" . DB_NAME . "' exists\n";
    echo "4. Verify user '" . DB_USER . "' has access\n";
}
