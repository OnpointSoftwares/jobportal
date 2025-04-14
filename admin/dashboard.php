<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$conn = getConnection();

// Handle job approval/rejection
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $action = $_POST['action'];
    $jobId = (int)$_POST['job_id'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $sql = "UPDATE jobs SET approval_status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $status, $jobId);
        $stmt->execute();
        
        // Redirect to refresh the page
        header('Location: dashboard.php');
        exit();
    }
}

// Get statistics
$stats = array();

// Total users
$users_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN user_type = 'jobseeker' THEN 1 ELSE 0 END) as total_jobseekers,
    SUM(CASE WHEN user_type = 'recruiter' THEN 1 ELSE 0 END) as total_recruiters
    FROM users";
$users_result = $conn->query($users_sql);
$stats['users'] = $users_result->fetch_assoc();

// Total jobs and applications
$jobs_sql = "SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as active_jobs
    FROM jobs";
$jobs_result = $conn->query($jobs_sql);
$stats['jobs'] = $jobs_result->fetch_assoc();

$applications_sql = "SELECT COUNT(*) as total_applications FROM job_applications";
$applications_result = $conn->query($applications_sql);
$stats['applications'] = $applications_result->fetch_assoc();

// Recent users
$recent_users_sql = "SELECT id, name, email, user_type, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10";
$recent_users = $conn->query($recent_users_sql);

// Recent jobs
$recent_jobs_sql = "SELECT 
    j.id, j.title, j.status, j.created_at, j.approval_status,
    cp.company_name
    FROM jobs j
    JOIN company_profiles cp ON j.company_id = cp.id
    ORDER BY j.created_at DESC
    LIMIT 10";
$recent_jobs = $conn->query($recent_jobs_sql);

// Pending jobs for approval
$pending_jobs_sql = "SELECT 
    j.id, j.title, j.type, j.location, j.created_at,
    cp.company_name
    FROM jobs j
    JOIN company_profiles cp ON j.company_id = cp.id
    WHERE j.approval_status = 'pending'
    ORDER BY j.created_at ASC";
$pending_jobs = $conn->query($pending_jobs_sql);

// Job categories
$categories_sql = "SELECT 
    jc.id, jc.name,
    COUNT(jcr.job_id) as job_count
    FROM job_categories jc
    LEFT JOIN job_category_relations jcr ON jc.id = jcr.category_id
    GROUP BY jc.id
    ORDER BY job_count DESC";
$categories = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Job Portal</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 600;
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

        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-table th {
            background: #f8fafc;
            font-weight: 600;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .category-card {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .category-card h4 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
<?php include("includes/bootstrap_header.php"); ?>


    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div>
                <a href="users.php" class="btn btn-primary">Manage Users</a>
                <a href="jobs.php" class="btn btn-primary">Manage Jobs</a>
            </div>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['users']['total_users']; ?></div>
                <p>Job Seekers: <?php echo $stats['users']['total_jobseekers']; ?></p>
                <p>Recruiters: <?php echo $stats['users']['total_recruiters']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Jobs</h3>
                <div class="number"><?php echo $stats['jobs']['total_jobs']; ?></div>
                <p>Active: <?php echo $stats['jobs']['active_jobs']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Applications</h3>
                <div class="number"><?php echo $stats['applications']['total_applications']; ?></div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" data-tab="users">Recent Users</button>
            <button class="tab" data-tab="jobs">Recent Jobs</button>
            <button class="tab" data-tab="categories">Job Categories</button>
        </div>

        <div id="users" class="tab-content active">
            <div class="content-section">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-success">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="approveUser(<?php echo $user['id']; ?>)">Approve User</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="jobs" class="tab-content">
            <div class="content-section">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $job['status'] === 'open' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                    <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="approveJob(<?php echo $job['id']; ?>)">Approve Job</button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteJob(<?php echo $job['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="categories" class="tab-content">
            <div class="content-section">
                <div style="margin-bottom: 2rem;">
                    <button class="btn btn-primary" onclick="showAddCategoryModal()">Add Category</button>
                </div>
                <div class="categories-grid">
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <div class="category-card">
                            <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                            <p><?php echo $category['job_count']; ?> Jobs</p>
                            <button onclick="editCategory(<?php echo $category['id']; ?>)" class="btn btn-primary">Edit</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function approveUser(userId) {
    // AJAX request to approve user
    fetch('edit_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId + '&action=approve'
    })
    .then(response => response.text())
    .then(data => {
        // Handle success or error
        alert("User Approved");
        location.reload(); // Reload the page to see changes
    });
}

function approveJob(jobId) {
    // AJAX request to approve job
    fetch('manage_jobs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'job_id=' + jobId + '&action=approve'
    })
    .then(response => response.text())
    .then(data => {
        // Handle success or error
        alert("Job Approved");
        location.reload(); // Reload the page to see changes
    });
}
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

        // User management functions
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch(`delete-user.php?id=${userId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting user');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting user');
                });
            }
        }

        // Job management functions
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                fetch(`delete-job.php?id=${jobId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting job');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting job');
                });
            }
        }

        // Category management functions
        function showAddCategoryModal() {
            const categoryName = prompt('Enter category name:');
            if (categoryName) {
                fetch('add-category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ name: categoryName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error adding category');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding category');
                });
            }
        }

        function editCategory(categoryId) {
            // Implement category editing functionality
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
