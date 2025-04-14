<?php
// manage_jobs.php

include '../includes/admin_header.php';
include '../config/database.php';
$conn=getConnection();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jobId = $_POST['job_id'];
    $action = $_POST['action'] ?? null;

    if ($action === 'approve') {
        $query = "UPDATE jobs SET approval_status = 'approved' WHERE id = ?";
    } elseif ($action === 'reject') {
        $query = "UPDATE jobs SET approval_status = 'rejected' WHERE id = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    // Optionally return a response
    echo json_encode(['status' => 'success']);
}
?>