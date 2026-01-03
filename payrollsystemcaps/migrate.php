<?php
// migrate.php
// RUN THIS IN TERMINAL: php migrate.php

// ==========================================
// 1. DATABASE CONFIGURATION
// ==========================================
// Adjust these to match your local XAMPP/Database settings
$host = '127.0.0.1';
$db   = 'payrollcapsdb'; // <--- CHANGE THIS to your actual database name
$user = 'root';
$pass = '';

echo "--------------------------------------\n";
echo "       Custom PHP Migration Tool      \n";
echo "--------------------------------------\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✔ Database Connected.\n";
} catch (PDOException $e) {
    die("❌ Connection Failed: " . $e->getMessage() . "\n");
}

// ==========================================
// 2. SETUP MIGRATIONS TABLE
// ==========================================
// This table acts like Laravel's 'migrations' table
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==========================================
// 3. SCAN AND EXECUTE
// ==========================================
$files = glob(__DIR__ . '/migrations/*.php');
$stmt = $pdo->query("SELECT migration FROM migrations");
$appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$newMigrations = [];

foreach ($files as $file) {
    $filename = basename($file);

    // Skip if already migrated
    if (in_array($filename, $appliedMigrations)) {
        continue;
    }

    echo "Migrating: $filename... ";

    require $file;

    // Convert filename to class name (e.g. 001_users.php -> Users)
 
    // 1. Get filename without extension
    $className = pathinfo($filename, PATHINFO_FILENAME);
    // 2. Remove the timestamp (digits and underscores at start)
    $className = preg_replace('/^[\d_]+/', '', $className); 
    // 3. Convert snake_case to PascalCase
    $className = str_replace('_', '', ucwords($className, '_'));

    if (!class_exists($className)) {
        echo "❌ Error: Class '$className' not found in file.\n";
        exit;
    }

    try {
        $migration = new $className();
        
        // Check if up() exists
        if(method_exists($migration, 'up')) {
            $migration->up($pdo);
        } else {
            echo "❌ Error: No up() method found.\n"; 
            exit;
        }

        // Log it
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$filename]);
        
        echo "DONE.\n";
        $newMigrations[] = $filename;

    } catch (Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (empty($newMigrations)) {
    echo "Nothing to migrate.\n";
} else {
    echo "Migration complete.\n";
}