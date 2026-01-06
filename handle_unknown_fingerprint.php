<?php
/**
 * Handle Unknown Fingerprint Events
 * Logs unrecognized fingerprint scans for security monitoring
 */

require_once 'db_connection.php';

// Create table if it doesn't exist
$create_table = "
CREATE TABLE IF NOT EXISTS unknown_fingerprints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    device_id VARCHAR(100),
    confidence INT DEFAULT 0,
    reason VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
)";
$conn->query($create_table);

// Insert unknown fingerprint log
function logUnknownFingerprint($device_id, $confidence = 0, $reason = 'not_enrolled') {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO unknown_fingerprints 
        (device_id, confidence, reason) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->bind_param("sis", $device_id, $confidence, $reason);
    $result = $stmt->execute();
    
    if ($result) {
        return $conn->insert_id;
    }
    return false;
}

// Get recent unknown scans for display
function getRecentUnknownScans($limit = 10) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            id,
            scan_timestamp,
            device_id,
            confidence,
            reason,
            created_at
        FROM unknown_fingerprints
        ORDER BY created_at DESC
        LIMIT ?
    ");
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $scans = [];
    while ($row = $result->fetch_assoc()) {
        $scans[] = $row;
    }
    
    return $scans;
}
?>