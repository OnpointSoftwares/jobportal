<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['application_id']) || !isset($_POST['status'])) {
    header("Location: dashboard.php");
    exit();
}

$application_id = $_POST['application_id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    header("Location: dashboard.php");
    exit();
}

$conn = getConnection();

// Get company profile
$company_sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $_SESSION['user_id']);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();

if (!$company) {
    header("Location: dashboard.php");
    exit();
}

// Verify that the application belongs to a job from this recruiter's company
$verify_sql = "SELECT ja.id, j.id as job_id 
               FROM job_applications ja 
               JOIN jobs j ON ja.job_id = j.id 
               WHERE ja.id = ? AND j.company_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $application_id, $company['id']);
$verify_stmt->execute();
$result = $verify_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$application = $result->fetch_assoc();
$job_id = $application['job_id'];

// Update application status
$update_sql = "UPDATE job_applications SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $status, $application_id);
$update_stmt->execute();

// Add notification for the job seeker
$get_user_sql = "SELECT user_id FROM job_applications WHERE id = ?";
$get_user_stmt = $conn->prepare($get_user_sql);
$get_user_stmt->bind_param("i", $application_id);
$get_user_stmt->execute();
$user_result = $get_user_stmt->get_result();
$user = $user_result->fetch_assoc();

if ($user) {
    $job_title_sql = "SELECT title FROM jobs WHERE id = ?";
    $job_title_stmt = $conn->prepare($job_title_sql);
    $job_title_stmt->bind_param("i", $job_id);
    $job_title_stmt->execute();
    $job_title_result = $job_title_stmt->get_result();
    $job = $job_title_result->fetch_assoc();
    
    $title = "Application Status Updated";
    $message = "Your application for the position of " . $job['title'] . " has been updated to " . ucfirst($status) . ".";
    
    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'application_update')";
    $notification_stmt = $conn->prepare($notification_sql);
    $notification_stmt->bind_param("iss", $user['user_id'], $title, $message);
    $notification_stmt->execute();
}

// Redirect back to the view applications page
header("Location: view-applications.php?job_id=" . $job_id);
exit();

$conn->close();
?>
