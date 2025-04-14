<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$conn = getConnection();

// Get job seeker's profile and calculate completion percentage
$profile_sql = "SELECT jp.*, u.name, u.email FROM jobseeker_profiles jp 
                RIGHT JOIN users u ON jp.user_id = u.id 
                WHERE u.id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $_SESSION['user_id']);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();

// Calculate profile completion percentage
$completion_items = [
    'phone' => 15,
    'address' => 15,
    'skills' => 20,
    'experience' => 20,
    'education' => 15,
    'resume_path' => 15
];
$completion_percentage = 0;
foreach ($completion_items as $field => $weight) {
    if (!empty($profile[$field])) {
        $completion_percentage += $weight;
    }
}

// Get job applications
$applications_sql = "SELECT 
    ja.id as application_id,
    ja.status as application_status,
    ja.created_at as applied_date,
    j.title as job_title,
    j.location as job_location,
    j.type as job_type,
    j.salary_range,
    cp.company_name,
    cp.logo_path
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN company_profiles cp ON j.company_id = cp.id
    WHERE ja.user_id = ?
    ORDER BY ja.created_at DESC";
$applications_stmt = $conn->prepare($applications_sql);
$applications_stmt->bind_param("i", $_SESSION['user_id']);
$applications_stmt->execute();
$applications = $applications_stmt->get_result();

// Get saved jobs
$saved_sql = "SELECT 
    j.id as job_id,
    j.title as job_title,
    j.location as job_location,
    j.type as job_type,
    j.salary_range,
    cp.company_name,
    cp.logo_path,
    sj.created_at as saved_date
    FROM saved_jobs sj
    JOIN jobs j ON sj.job_id = j.id
    JOIN company_profiles cp ON j.company_id = cp.id
    WHERE sj.user_id = ?
    ORDER BY sj.created_at DESC";
$saved_stmt = $conn->prepare($saved_sql);
$saved_stmt->bind_param("i", $_SESSION['user_id']);
$saved_stmt->execute();
$saved_jobs = $saved_stmt->get_result();

// Get application statistics
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
    SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted_applications
    FROM job_applications 
    WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Seeker Dashboard - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-completion {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .progress {
            height: 10px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        .application-card {
            transition: transform 0.3s;
        }
        .application-card:hover {
            transform: translateY(-5px);
        }
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
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
                        <a class="nav-link" href="../jobs.php">Browse Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../companies.php">Companies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Profile Completion Section -->
        <div class="profile-completion">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-3">Profile Completion</h4>
                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $completion_percentage; ?>%"
                             aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <p class="mb-0">
                        Your profile is <?php echo $completion_percentage; ?>% complete. 
                        <?php if ($completion_percentage < 100): ?>
                            A complete profile helps you stand out to employers!
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="edit-profile.php" class="btn btn-primary">
                        <?php echo $completion_percentage < 100 ? 'Complete Profile' : 'Edit Profile'; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-paper-plane"></i>
                    <h3><?php echo $stats['total_applications']; ?></h3>
                    <p class="text-muted mb-0">Total Applications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending_applications']; ?></h3>
                    <p class="text-muted mb-0">Pending Review</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $stats['reviewed_applications']; ?></h3>
                    <p class="text-muted mb-0">Reviewed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $stats['shortlisted_applications']; ?></h3>
                    <p class="text-muted mb-0">Shortlisted</p>
                </div>
            </div>
        </div>

        <!-- Applications and Saved Jobs Tabs -->
        <ul class="nav nav-pills mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="applications-tab" data-bs-toggle="pill" 
                        data-bs-target="#applications" type="button" role="tab">
                    My Applications
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="saved-tab" data-bs-toggle="pill" 
                        data-bs-target="#saved" type="button" role="tab">
                    Saved Jobs
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Applications Tab -->
            <div class="tab-pane fade show active" id="applications" role="tabpanel">
                <?php if ($applications->num_rows > 0): ?>
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <div class="card application-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <img src="<?php echo $application['logo_path'] ? htmlspecialchars($application['logo_path']) : '../images/default-company.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($application['company_name']); ?>" 
                                             class="company-logo mb-2">
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($application['job_title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($application['company_name']); ?>
                                            <i class="fas fa-map-marker-alt ms-3 me-1"></i> <?php echo htmlspecialchars($application['job_location']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <small class="text-muted">
                                                Applied on <?php echo date('M j, Y', strtotime($application['applied_date'])); ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="col-md-2">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-warning',
                                            'reviewed' => 'bg-info',
                                            'shortlisted' => 'bg-success',
                                            'rejected' => 'bg-danger'
                                        ];
                                        $status_color = $status_colors[$application['application_status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $status_color; ?> status-badge">
                                            <?php echo ucfirst($application['application_status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="../job-details.php?id=<?php echo $application['job_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">View Job</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5>No Applications Yet</h5>
                        <p class="text-muted">Start applying to jobs to see your applications here</p>
                        <a href="../jobs.php" class="btn btn-primary">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Saved Jobs Tab -->
            <div class="tab-pane fade" id="saved" role="tabpanel">
                <?php if ($saved_jobs->num_rows > 0): ?>
                    <?php while ($job = $saved_jobs->fetch_assoc()): ?>
                        <div class="card application-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <img src="<?php echo $job['logo_path'] ? htmlspecialchars($job['logo_path']) : '../images/default-company.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                             class="company-logo mb-2">
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                            <i class="fas fa-map-marker-alt ms-3 me-1"></i> <?php echo htmlspecialchars($job['job_location']); ?>
                                        </p>
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                            <?php if($job['salary_range']): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                    <?php echo htmlspecialchars($job['salary_range']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="../job-details.php?id=<?php echo $job['job_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm mb-2">View Details</a>
                                        <form action="unsave-job.php" method="POST" class="d-inline">
                                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-heart-broken"></i> Unsave
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                        <h5>No Saved Jobs</h5>
                        <p class="text-muted">Save jobs you're interested in to apply later</p>
                        <a href="../jobs.php" class="btn btn-primary">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
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
