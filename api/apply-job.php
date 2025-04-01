<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to apply for jobs']);
    exit();
}

// Check if user is a job seeker
if ($_SESSION['user_type'] !== 'jobseeker') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only job seekers can apply for jobs']);
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
    
    // Check if job exists and is open
    $check_sql = "SELECT id FROM jobs WHERE id = ? AND status = 'open'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $jobId);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found or is no longer open']);
        exit();
    }
    
    // Check if already applied
    $applied_sql = "SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?";
    $applied_stmt = $conn->prepare($applied_sql);
    $applied_stmt->bind_param("ii", $jobId, $_SESSION['user_id']);
    $applied_stmt->execute();
    $applied_result = $applied_stmt->get_result();
    
    if ($applied_result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
        exit();
    }
    
    // Insert application
    $sql = "INSERT INTO job_applications (job_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $jobId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Create notification for the job poster
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                           SELECT cp.user_id, 
                                  'New Job Application', 
                                  CONCAT('New application received for ', j.title), 
                                  'application'
                           FROM jobs j
                           JOIN company_profiles cp ON j.company_id = cp.id
                           WHERE j.id = ?";
        $notify_stmt = $conn->prepare($notification_sql);
        $notify_stmt->bind_param("i", $jobId);
        $notify_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } else {
        throw new Exception('Error submitting application');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error submitting application']);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($applied_stmt)) $applied_stmt->close();
    if (isset($notify_stmt)) $notify_stmt->close();
    if (isset($conn)) $conn->close();
}
?>
