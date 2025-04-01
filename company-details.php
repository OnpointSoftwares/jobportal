<?php
require_once 'config/database.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: companies.php');
    exit();
}

$conn = getConnection();
$company_id = (int)$_GET['id'];

// Get company details
$sql = "SELECT * FROM company_profiles WHERE id = $company_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header('Location: companies.php');
    exit();
}

$company = $result->fetch_assoc();

// Get company's active jobs
$jobs_sql = "SELECT * FROM jobs WHERE company_id = $company_id AND status = 'open' ORDER BY created_at DESC";
$jobs_result = $conn->query($jobs_sql);

// Get total number of employees who applied to this company's jobs
$employees_sql = "SELECT COUNT(DISTINCT user_id) as total_applicants 
                 FROM job_applications ja 
                 JOIN jobs j ON ja.job_id = j.id 
                 WHERE j.company_id = $company_id";
$employees_result = $conn->query($employees_sql);
$total_applicants = $employees_result->fetch_assoc()['total_applicants'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company['company_name']); ?> - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .company-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .company-logo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 4px solid white;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 20px;
        }
        .job-card {
            transition: transform 0.3s;
        }
        .job-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">JobPortal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">Browse Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="companies.php">Companies</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Company Header -->
    <header class="company-header">
        <div class="container text-center">
            <img src="<?php echo $company['logo_path'] ? htmlspecialchars($company['logo_path']) : 'images/default-company.png'; ?>" 
                 alt="<?php echo htmlspecialchars($company['company_name']); ?>" 
                 class="company-logo mb-4">
            <h1 class="display-4 mb-2"><?php echo htmlspecialchars($company['company_name']); ?></h1>
            <p class="lead mb-4">
                <i class="fas fa-industry me-2"></i><?php echo htmlspecialchars($company['industry']); ?>
                <i class="fas fa-map-marker-alt ms-3 me-2"></i><?php echo htmlspecialchars($company['location']); ?>
            </p>
            <?php if($company['website']): ?>
                <a href="<?php echo htmlspecialchars($company['website']); ?>" class="btn btn-light" target="_blank">
                    <i class="fas fa-globe me-2"></i>Visit Website
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Company Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- About Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h3 mb-4">About <?php echo htmlspecialchars($company['company_name']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                    </div>
                </div>

                <!-- Open Positions -->
                <h2 class="h3 mb-4">Open Positions</h2>
                <?php if ($jobs_result->num_rows > 0): ?>
                    <?php while($job = $jobs_result->fetch_assoc()): ?>
                        <div class="card job-card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($job['location']); ?>
                                            <span class="ms-3">
                                                <i class="fas fa-clock me-1"></i> <?php echo htmlspecialchars($job['type']); ?>
                                            </span>
                                        </p>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <?php if($job['salary_range']): ?>
                                            <p class="text-success mb-2"><?php echo htmlspecialchars($job['salary_range']); ?></p>
                                        <?php endif; ?>
                                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        No open positions available at the moment.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Company Stats -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-4">Company Statistics</h3>
                        <div class="row">
                            <div class="col-6">
                                <div class="stat-card">
                                    <h4 class="h2 mb-2"><?php echo $jobs_result->num_rows; ?></h4>
                                    <p class="text-muted mb-0">Open Positions</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <h4 class="h2 mb-2"><?php echo $total_applicants; ?></h4>
                                    <p class="text-muted mb-0">Total Applicants</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="h5 mb-4">Company Information</h3>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-industry me-2"></i>
                                Industry: <?php echo htmlspecialchars($company['industry']); ?>
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Location: <?php echo htmlspecialchars($company['location']); ?>
                            </li>
                            <?php if($company['website']): ?>
                                <li class="mb-3">
                                    <i class="fas fa-globe me-2"></i>
                                    Website: <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($company['website']); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
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
