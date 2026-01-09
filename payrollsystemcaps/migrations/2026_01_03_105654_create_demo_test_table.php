<?php

class CreateDemoTestTable {
    public function up($pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS demo_test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_name VARCHAR(50) NOT NULL,
            test_number INT DEFAULT 0,
            test_price DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
    }

    public function down($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS demo_test_table");
    }
}