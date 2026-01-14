<?php
/**
 * Automated Database Installer for Online Examination System (PostgreSQL)
 * 
 * This script automates the database setup process for PostgreSQL/Supabase:
 * 1. Connects to PostgreSQL database
 * 2. Imports the schema
 * 3. Seeds initial data
 * 4. Verifies the installation
 * 
 * Usage: php database/install_postgresql.php
 */

// Color output for terminal
class Colors {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color
}

function printSuccess($message) {
    echo Colors::$GREEN . "✓ " . $message . Colors::$NC . "\n";
}

function printError($message) {
    echo Colors::$RED . "✗ " . $message . Colors::$NC . "\n";
}

function printInfo($message) {
    echo Colors::$BLUE . "ℹ " . $message . Colors::$NC . "\n";
}

function printWarning($message) {
    echo Colors::$YELLOW . "⚠ " . $message . Colors::$NC . "\n";
}

function printHeader($message) {
    echo "\n" . Colors::$BLUE . "═══════════════════════════════════════════════════════" . Colors::$NC . "\n";
    echo Colors::$BLUE . "  " . $message . Colors::$NC . "\n";
    echo Colors::$BLUE . "═══════════════════════════════════════════════════════" . Colors::$NC . "\n\n";
}

// Start installation
printHeader("Online Examination System - PostgreSQL Installer");

// Step 1: Load configuration
printInfo("Loading database configuration...");

$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    printError("Configuration file not found: $configFile");
    printInfo("Please create config/database.php with your database settings.");
    exit(1);
}

// Check if .env file exists
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    printWarning(".env file not found!");
    printInfo("Creating .env file from .env.example...");
    
    $envExample = __DIR__ . '/../.env.example';
    if (file_exists($envExample)) {
        copy($envExample, $envFile);
        printSuccess(".env file created");
        printWarning("Please edit .env file with your PostgreSQL/Supabase credentials before continuing.");
        printInfo("Update DB_CONNECTION=pgsql and set your Supabase connection details.");
        printInfo("Then run this installer again: php database/install_postgresql.php");
        exit(0);
    } else {
        printError(".env.example file not found");
        printInfo("Please create .env file manually with database configuration.");
        exit(1);
    }
}

require_once $configFile;

// Check if constants are defined
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    printError("Database configuration constants not found.");
    printInfo("Please ensure DB_HOST, DB_NAME, DB_USER, and DB_PASS are defined in .env");
    exit(1);
}

// Check if using PostgreSQL
if (DB_CONNECTION !== 'pgsql') {
    printWarning("DB_CONNECTION is set to '" . DB_CONNECTION . "' but this installer is for PostgreSQL.");
    printInfo("Please set DB_CONNECTION=pgsql in your .env file.");
    printInfo("Or use 'php database/install.php' for MySQL.");
    exit(1);
}

printSuccess("Configuration loaded successfully");
printInfo("Database: " . DB_NAME);
printInfo("Host: " . DB_HOST);
printInfo("Port: " . DB_PORT);
printInfo("User: " . DB_USER);
printInfo("Connection: PostgreSQL");

// Step 2: Connect to PostgreSQL
printInfo("\nConnecting to PostgreSQL server...");

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    printSuccess("Connected to PostgreSQL database");
} catch (PDOException $e) {
    printError("Failed to connect to PostgreSQL server");
    printError("Error: " . $e->getMessage());
    printInfo("\nPlease check:");
    printInfo("1. PostgreSQL server is accessible");
    printInfo("2. Database credentials in .env are correct");
    printInfo("3. Your IP is whitelisted (for Supabase)");
    printInfo("4. Database name exists");
    exit(1);
}

// Step 3: Import schema
printInfo("\nImporting database schema...");

$schemaFile = __DIR__ . '/schema_postgresql.sql';
if (!file_exists($schemaFile)) {
    printError("Schema file not found: $schemaFile");
    exit(1);
}

$schema = file_get_contents($schemaFile);
if ($schema === false) {
    printError("Failed to read schema file");
    exit(1);
}

try {
    // Execute the entire schema as one transaction
    $pdo->exec($schema);
    
    printSuccess("Schema imported successfully");
    
    // Count tables created
    $result = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
    $tableCount = $result->fetchColumn();
    printInfo("Created/verified $tableCount tables");
    
} catch (PDOException $e) {
    printError("Failed to import schema");
    printError("Error: " . $e->getMessage());
    exit(1);
}

// Step 4: Import seed data
printInfo("\nImporting seed data...");

$seedFile = __DIR__ . '/seed_postgresql.sql';
if (!file_exists($seedFile)) {
    printWarning("Seed file not found: $seedFile");
    printInfo("Skipping seed data import");
} else {
    $seed = file_get_contents($seedFile);
    if ($seed === false) {
        printError("Failed to read seed file");
        exit(1);
    }
    
    try {
        // Remove all comment lines first
        $lines = explode("\n", $seed);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            // Skip empty lines and comment lines
            if (!empty($trimmedLine) && !preg_match('/^--/', $trimmedLine)) {
                $cleanLines[] = $line;
            }
        }
        $cleanSeed = implode("\n", $cleanLines);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $cleanSeed)),
            function($stmt) {
                return !empty($stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        printSuccess("Seed data imported successfully");
        
        // Count users created
        $result = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
        printInfo("Created $userCount user accounts");
        
        // Count questions created
        $result = $pdo->query("SELECT COUNT(*) as count FROM questions");
        $questionCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
        printInfo("Created $questionCount sample questions");
        
        // Count exams created
        $result = $pdo->query("SELECT COUNT(*) as count FROM exams");
        $examCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
        printInfo("Created $examCount ready-to-take exams");
        
    } catch (PDOException $e) {
        printError("Failed to import seed data");
        printError("Error: " . $e->getMessage());
        printWarning("You may need to manually create user accounts");
    }
}

// Step 5: Verify installation
printInfo("\nVerifying installation...");

try {
    // Check required tables exist
    $requiredTables = [
        'users',
        'questions',
        'question_options',
        'exams',
        'exam_questions',
        'exam_sessions',
        'answers'
    ];
    
    $result = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
    $existingTables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $allTablesExist = true;
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            printError("Required table '$table' not found");
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist) {
        printSuccess("All required tables exist");
    } else {
        printError("Some required tables are missing");
        exit(1);
    }
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($adminCount > 0) {
        printSuccess("Admin account found");
    } else {
        printWarning("No admin account found");
        printInfo("You may need to manually create an admin account");
    }
    
    // Check if student users exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($studentCount > 0) {
        printSuccess("Student accounts found ($studentCount)");
    } else {
        printWarning("No student accounts found");
        printInfo("You may need to manually create student accounts");
    }
    
} catch (PDOException $e) {
    printError("Verification failed");
    printError("Error: " . $e->getMessage());
    exit(1);
}

// Step 6: Success message
printHeader("Installation Complete!");

printSuccess("PostgreSQL database setup completed successfully!\n");

printInfo("Next steps:");
echo "  1. Start the development server:\n";
echo "     " . Colors::$YELLOW . "cd public && php -S localhost:8000" . Colors::$NC . "\n\n";
echo "  2. Open your browser:\n";
echo "     " . Colors::$YELLOW . "http://localhost:8000" . Colors::$NC . "\n\n";
echo "  3. Login with test credentials:\n";
echo "     Admin:    " . Colors::$GREEN . "admin / admin123" . Colors::$NC . "\n";
echo "     Student:  " . Colors::$GREEN . "student1 / pass123" . Colors::$NC . "\n";
echo "     Student:  " . Colors::$GREEN . "student2 / pass123" . Colors::$NC . "\n\n";

printInfo("For more information, see README.md");

echo "\n";
exit(0);
