<?php
/**
 * API: Get Latest Unknown Fingerprint Scans
 * For real-time display in bio.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db_connection.php';

$last_id = (int)($_GET['last_id'] ?? 0);

// Get latest unknown scan
$stmt = $conn->prepare("
    SELECT 
        id,
        scan_timestamp,
        device_id,
        confidence,
        reason,
        created_at
    FROM unknown_fingerprints
    WHERE id > ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'scan' => [
            'id' => $row['id'],
            'timestamp' => $row['created_at'],
            'device' => $row['device_id'],
            'confidence' => $row['confidence'],
            'reason' => $row['reason']
        ]
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>