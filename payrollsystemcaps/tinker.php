<?php
// tinker.php - A simple REPL for your native PHP project

// 1. LOAD YOUR ENVIRONMENT (Database, etc.)
// ==========================================
$host = '127.0.0.1';
$db   = 'payrollcapsdb'; // <--- CHANGE THIS
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✔ Database Connected (Available as \$pdo)\n";
} catch (PDOException $e) {
    die("❌ DB Error: " . $e->getMessage() . "\n");
}

// 2. DEFINE HELPER FUNCTIONS (The "Magic" Commands)
// ==========================================

// Function to generate a new migration file
// Usage in tinker: make('add_users_table')
function make($name) {
    $timestamp = date('Y_m_d_His'); // Laravel style timestamp
    $filename = "migrations/{$timestamp}_{$name}.php";
    
    // Convert 'add_users_table' to 'AddUsersTable'
    $className = str_replace('_', '', ucwords($name, '_'));

    $template = "<?php

class $className {
    public function up(\$pdo) {
        \$sql = \"CREATE TABLE IF NOT EXISTS ... (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )\";
        \$pdo->exec(\$sql);
    }

    public function down(\$pdo) {
        \$sql = \"DROP TABLE IF EXISTS ...\";
        \$pdo->exec(\$sql);
    }
}";

    if (!is_dir('migrations')) { mkdir('migrations'); }
    
    file_put_contents($filename, $template);
    return "✅ Created Migration: $filename";
}

// Function to run raw SQL quickly
// Usage: sql('SELECT * FROM users')
function sql($query) {
    global $pdo;
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    } catch (Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
}

// 3. START THE LOOP ( The "Tinker" Shell)
// ==========================================
echo "------------------------------------------\n";
echo " Native PHP Tinker \n";
echo " Type 'exit' to quit.\n";
echo " Usage: make('migration_name') to create files.\n";
echo " Usage: \$pdo->query(...) to use database.\n";
echo "------------------------------------------\n";

while (true) {
    // Get user input
    echo "\n>>> ";
    $line = fgets(STDIN);
    
    // Check for exit
    if (trim($line) === 'exit') {
        echo "Bye!\n";
        break;
    }

    // Execute the code
    try {
        // We use eval() to run the PHP code typed in the terminal
        // Note: We add 'return' so we can print the output, unless it's an echo
        if (strpos($line, 'echo') === false && strpos($line, 'return') === false) {
             $line = "return " . $line . ";";
        }
        
        $result = eval($line);
        
        // Pretty print the result
        if ($result !== null) {
            print_r($result);
        }
        
    } catch (ParseError $e) {
        echo "❌ Syntax Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage();
    }
}