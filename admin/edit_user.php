<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/database.php';
$conn=getConnection();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {// edit_user.php
    $userId = $_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $query = "UPDATE users SET status = 'approved' WHERE id = ?";
    } elseif ($action === 'reject') {
        $query = "UPDATE users SET status = 'rejected' WHERE id = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    // Optionally return a response
    echo json_encode(['status' => 'success']);
}
?>