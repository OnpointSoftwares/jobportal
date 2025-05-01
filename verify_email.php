<?php
require_once 'config/database.php';

$verified = false;
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $conn = getConnection();
    $sql = "SELECT id, email_verified FROM users WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['email_verified']) {
            $error = "Your email is already verified.";
        } else {
            $update_sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $token);
            if ($update_stmt->execute()) {
                $verified = true;
            } else {
                $error = "Verification failed. Please try again.";
            }
            $update_stmt->close();
        }
    } else {
        $error = "Invalid or expired verification link.";
    }
    $stmt->close();
    $conn->close();
} else {
    $error = "Invalid request.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="register-container">
        <h2>Email Verification</h2>
        <?php if ($verified): ?>
            <div class="success-message">Your email has been verified. You can now <a href="login.php">login</a>.</div>
        <?php else: ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
