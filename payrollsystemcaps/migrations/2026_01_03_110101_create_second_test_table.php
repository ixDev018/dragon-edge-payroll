<?php

class CreateSecondTestTable {
    public function up($pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS second_test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,    -- Testing boolean
            start_date DATE,                   -- Testing date
            budget DECIMAL(10,2),              -- Testing decimal
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
    }

    public function down($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS second_test_table");
    }
}