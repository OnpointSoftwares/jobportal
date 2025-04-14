<?php
session_start();
require_once 'config/database.php';

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$conn = getConnection();
$job_id = $_GET['id'];

// Get job details with company information
$sql = "SELECT j.*, cp.company_name, cp.logo_path, cp.website, cp.description as company_description 
        FROM jobs j 
        JOIN company_profiles cp ON j.company_id = cp.id 
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: jobs.php");
    exit();
}

// Check if user has already applied
$has_applied = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker') {
    $check_sql = "SELECT id, status FROM job_applications WHERE job_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $existing_application = $check_stmt->get_result()->fetch_assoc();
    $has_applied = (bool)$existing_application;
}

// Handle job application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker') {
    $user_id = $_SESSION['user_id'];
    $cover_letter = $_POST['cover_letter'] ?? '';
    
    // Check if user has completed their profile
    $profile_sql = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
    $profile_stmt = $conn->prepare($profile_sql);
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $profile = $profile_stmt->get_result()->fetch_assoc();
    
    if (!$profile || !$profile['resume_path']) {
        $error = "Please complete your profile and upload a resume before applying.";
    } else {
        // Insert application
        $apply_sql = "INSERT INTO job_applications (job_id, user_id, cover_letter, status) VALUES (?, ?, ?, 'pending')";
        $apply_stmt = $conn->prepare($apply_sql);
        $apply_stmt->bind_param("iis", $job_id, $user_id, $cover_letter);
        
        if ($apply_stmt->execute()) {
            $success = "Your application has been submitted successfully!";
            $has_applied = true;
        } else {
            $error = "Error submitting application. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .company-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .job-header {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .apply-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include("includes/bootstrap_header.php"); ?>

    <div class="container py-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Job Header -->
        <div class="job-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="<?php echo $job['logo_path'] ? htmlspecialchars($job['logo_path']) : 'images/default-company.png'; ?>" 
                         alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                         class="company-logo mb-3">
                </div>
                <div class="col-md-7">
                    <h1 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p class="mb-2">
                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company_name']); ?>
                        <i class="fas fa-map-marker-alt ms-3 me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                    </p>
                    <div>
                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($job['type']); ?></span>
                        <?php if($job['salary_range']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-money-bill-wave me-1"></i>
                                <?php echo htmlspecialchars($job['salary_range']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker'): ?>
                        <?php if ($has_applied): ?>
                            <?php if (isset($existing_application)): ?>
                                <div class="mb-2">
                                    <span class="badge bg-info status-badge">
                                        Application Status: <?php echo ucfirst($existing_application['status']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <button class="btn btn-secondary" disabled>Already Applied</button>
                        <?php else: ?>
                            <a href="#apply-section" class="btn btn-primary">Apply Now</a>
                        <?php endif; ?>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn btn-primary">Login to Apply</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Job Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Job Description</h4>
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        
                        <?php if ($job['requirements']): ?>
                            <h5 class="mt-4">Requirements</h5>
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        <?php endif; ?>
                        
                        <?php if ($job['benefits']): ?>
                            <h5 class="mt-4">Benefits</h5>
                            <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Company Overview</h4>
                        <p><?php echo nl2br(htmlspecialchars($job['company_description'])); ?></p>
                        <?php if ($job['website']): ?>
                            <a href="<?php echo htmlspecialchars($job['website']); ?>" 
                               class="btn btn-outline-primary" 
                               target="_blank">
                                Visit Website
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Form -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker' && !$has_applied): ?>
            <div id="apply-section" class="apply-section">
                <h3 class="mb-4">Apply for this Position</h3>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $job_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" 
                                placeholder="Tell us why you're the perfect fit for this position..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php
    include('includes/bootstrap_footer.php');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
