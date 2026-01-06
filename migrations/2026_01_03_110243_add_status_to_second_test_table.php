<?php

class AddStatusToSecondTestTable {
    public function up($pdo) {
        // We use "ALTER TABLE" to modify an existing table
        // We are adding a 'status' column AFTER the 'project_name' column
        $sql = "ALTER TABLE second_test_table 
                ADD COLUMN status VARCHAR(20) DEFAULT 'pending' 
                AFTER project_name";
        
        $pdo->exec($sql);
    }

    public function down($pdo) {
        // The reverse is to drop the column
        $sql = "ALTER TABLE second_test_table DROP COLUMN status";
        $pdo->exec($sql);
    }
}