<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to save jobs']);
    exit();
}

// Check if user is a job seeker
if ($_SESSION['user_type'] !== 'jobseeker') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only job seekers can save jobs']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$jobId = $data['jobId'] ?? null;

if (!$jobId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit();
}

try {
    $conn = getConnection();
    
    // Check if job exists
    $check_sql = "SELECT id FROM jobs WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $jobId);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit();
    }
    
    // Try to insert the saved job
    $sql = "INSERT INTO saved_jobs (job_id, user_id) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $jobId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Job saved successfully']);
    } else {
        throw new Exception('Error saving job');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving job']);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($conn)) $conn->close();
}
?>
