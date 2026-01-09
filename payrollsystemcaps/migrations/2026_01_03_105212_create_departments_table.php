<?php

class CreateDepartmentsTable {
    public function up($pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dept_name VARCHAR(100) NOT NULL,
            manager_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
    }

    public function down($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS departments");
    }
}