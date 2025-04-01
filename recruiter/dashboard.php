<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$conn = getConnection();

// Get company profile
$company_sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $_SESSION['user_id']);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();

if ($company) {
    // Get posted jobs
    $jobs_sql = "SELECT 
        j.*,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as applications_count
        FROM jobs j
        WHERE j.company_id = ?
        ORDER BY j.created_at DESC";
    $jobs_stmt = $conn->prepare($jobs_sql);
    $jobs_stmt->bind_param("i", $company['id']);
    $jobs_stmt->execute();
    $jobs = $jobs_stmt->get_result();

    // Get recent applications
    $applications_sql = "SELECT 
        ja.id as application_id,
        ja.status as application_status,
        ja.created_at as applied_date,
        j.title as job_title,
        u.name as applicant_name,
        u.email as applicant_email,
        jp.resume_path
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.user_id = u.id
        LEFT JOIN jobseeker_profiles jp ON ja.user_id = jp.user_id
        WHERE j.company_id = ?
        ORDER BY ja.created_at DESC
        LIMIT 10";
    $applications_stmt = $conn->prepare($applications_sql);
    $applications_stmt->bind_param("i", $company['id']);
    $applications_stmt->execute();
    $applications = $applications_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruiter Dashboard - Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 8rem auto 4rem;
            padding: 0 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.8rem 1.5rem;
            background: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .tab.active {
            background: var(--primary-color);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }

        .company-section, .jobs-section, .applications-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .job-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
        }

        .applications-table th,
        .applications-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .applications-table th {
            background: #f8fafc;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-reviewed { background: #dbeafe; color: #1e40af; }
        .status-shortlisted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .company-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <a href="post-job.php" class="btn-primary">Post New Job</a>
        </div>

        <?php if (!$company): ?>
            <div class="company-section">
                <h2>Complete Your Company Profile</h2>
                <p>Please set up your company profile to start posting jobs and receiving applications.</p>
                <a href="company-profile.php" class="btn-primary" style="margin-top: 1rem;">Create Company Profile</a>
            </div>
        <?php else: ?>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Active Jobs</h3>
                    <p><?php echo $jobs->num_rows; ?> Posted</p>
                </div>
                <div class="stat-card">
                    <h3>Total Applications</h3>
                    <p><?php 
                        $total_applications = 0;
                        while ($job = $jobs->fetch_assoc()) {
                            $total_applications += $job['applications_count'];
                        }
                        $jobs->data_seek(0);
                        echo $total_applications;
                    ?> Received</p>
                </div>
                <div class="stat-card">
                    <h3>Profile Views</h3>
                    <p>0 Views</p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" data-tab="company">Company Profile</button>
                <button class="tab" data-tab="jobs">Posted Jobs</button>
                <button class="tab" data-tab="applications">Recent Applications</button>
            </div>

            <div id="company" class="tab-content active">
                <div class="company-section">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <img src="<?php echo htmlspecialchars($company['logo_path'] ?? '../images/default-company-logo.png'); ?>" alt="Company Logo" class="company-logo">
                            <h2><?php echo htmlspecialchars($company['company_name']); ?></h2>
                            <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                        </div>
                        <a href="edit-company.php" class="btn-primary">Edit Profile</a>
                    </div>
                    <div style="margin-top: 2rem;">
                        <p><strong>Industry:</strong> <?php echo htmlspecialchars($company['industry']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($company['location']); ?></p>
                        <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a></p>
                    </div>
                </div>
            </div>

            <div id="jobs" class="tab-content">
                <div class="jobs-section">
                    <div class="jobs-grid">
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="job-card">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                <p><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['type']); ?></p>
                                <p><i class="fas fa-users"></i> <?php echo $job['applications_count']; ?> Applications</p>
                                <div style="margin-top: 1rem;">
                                    <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="btn-primary">View Applications</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div id="applications" class="tab-content">
                <div class="applications-section">
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($application = $applications->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($application['applicant_name']); ?></strong>
                                            <br>
                                            <?php echo htmlspecialchars($application['applicant_email']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($application['application_status']); ?>">
                                            <?php echo ucfirst($application['application_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($application['resume_path']): ?>
                                            <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" target="_blank" class="btn-primary">View Resume</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update active content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
