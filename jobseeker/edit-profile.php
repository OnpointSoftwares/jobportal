<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get existing profile data
$profile_sql = "SELECT jp.*, u.name, u.email 
                FROM jobseeker_profiles jp 
                RIGHT JOIN users u ON jp.user_id = u.id 
                WHERE u.id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $skills = $_POST['skills'];
    $experience = $_POST['experience'];
    $education = $_POST['education'];
    
    // Handle resume upload
    $resume_path = $profile['resume_path']; // Keep existing path by default
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['resume']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/resumes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $new_filename = 'resume_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
                // Delete old resume if exists
                if ($resume_path && file_exists($resume_path)) {
                    unlink($resume_path);
                }
                $resume_path = $target_path;
            } else {
                $error_message = "Failed to upload resume. Please try again.";
            }
        } else {
            $error_message = "Invalid file type. Please upload PDF or Word documents only.";
        }
    }

    if (empty($error_message)) {
        // Update user name
        $update_user_sql = "UPDATE users SET name = ? WHERE id = ?";
        $update_user_stmt = $conn->prepare($update_user_sql);
        $update_user_stmt->bind_param("si", $name, $user_id);
        $update_user_stmt->execute();

        // Check if profile exists
        if ($profile && isset($profile['id'])) {
            // Update existing profile
            $update_sql = "UPDATE jobseeker_profiles 
                          SET phone = ?, address = ?, skills = ?, experience = ?, 
                              education = ?, resume_path = ? 
                          WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssssi", $phone, $address, $skills, $experience, 
                                   $education, $resume_path, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $_SESSION['user_name'] = $name; // Update session name
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        } else {
            // Create new profile
            $insert_sql = "INSERT INTO jobseeker_profiles 
                          (user_id, phone, address, skills, experience, education, resume_path) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issssss", $user_id, $phone, $address, $skills, 
                                   $experience, $education, $resume_path);
            
            if ($insert_stmt->execute()) {
                $success_message = "Profile created successfully!";
                $_SESSION['user_name'] = $name; // Update session name
            } else {
                $error_message = "Error creating profile. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-form {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 500;
        }
        .current-resume {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">JobPortal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="profile-form">
            <h1 class="mb-4">Edit Profile</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Personal Information</h5>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                    ><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Professional Information</h5>

                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills</label>
                            <textarea class="form-control" id="skills" name="skills" rows="4" 
                                    placeholder="List your key skills (e.g., JavaScript, Project Management, Communication)"
                                    ><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="experience" class="form-label">Work Experience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="6" 
                                    placeholder="Describe your work experience"
                                    ><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="education" class="form-label">Education</label>
                            <textarea class="form-control" id="education" name="education" rows="4" 
                                    placeholder="List your educational qualifications"
                                    ><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Resume</h5>

                        <?php if ($profile['resume_path']): ?>
                            <div class="current-resume">
                                <i class="fas fa-file-alt me-2"></i>
                                Current Resume: 
                                <a href="<?php echo htmlspecialchars($profile['resume_path']); ?>" target="_blank">
                                    View Resume
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="resume" class="form-label">Upload New Resume</label>
                            <input type="file" class="form-control" id="resume" name="resume" 
                                   accept=".pdf,.doc,.docx">
                            <small class="text-muted">Accepted formats: PDF, DOC, DOCX</small>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="dashboard.php" class="btn btn-light me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JobPortal</h5>
                    <p>Find your dream job with us</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact Us</h5>
                    <p>Email: support@jobportal.com</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> JobPortal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
