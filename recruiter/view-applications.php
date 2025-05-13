<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

// Check if job_id is provided
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    header("Location: dashboard.php");
    exit();
}

$job_id = $_GET['job_id'];
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

// Verify that the job belongs to this recruiter's company
$job_sql = "SELECT * FROM jobs WHERE id = ? AND company_id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param("ii", $job_id, $company['id']);
$job_stmt->execute();
$job = $job_stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: dashboard.php");
    exit();
}

// Get applications for this job
$applications_sql = "SELECT 
    ja.id as application_id,
    ja.status as application_status,
    ja.created_at as applied_date,
    ja.cover_letter,
    j.title as job_title,
    u.name as applicant_name,
    u.email as applicant_email,
    jp.resume_path,
    jp.phone,
    jp.skills,
    jp.experience,
    jp.education
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON ja.user_id = u.id
    LEFT JOIN jobseeker_profiles jp ON ja.user_id = jp.user_id
    WHERE ja.job_id = ?
    ORDER BY ja.created_at DESC";
$applications_stmt = $conn->prepare($applications_sql);
$applications_stmt->bind_param("i", $job_id);
$applications_stmt->execute();
$applications = $applications_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - <?php echo htmlspecialchars($job['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: #3490dc;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }
        
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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
        
        .status-pending {
            background: #f8fafc;
            color: #4a5568;
        }
        
        .status-reviewed {
            background: #ebf8ff;
            color: #2b6cb0;
        }
        
        .status-shortlisted {
            background: #e6fffa;
            color: #2c7a7b;
        }
        
        .status-rejected {
            background: #fff5f5;
            color: #c53030;
        }
        
        .application-detail {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
            border: 1px solid #e2e8f0;
        }
        
        .application-detail h3 {
            margin-top: 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .detail-section {
            margin-bottom: 1rem;
        }
        
        .detail-section h4 {
            margin-bottom: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 10px;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-form {
            margin-top: 1rem;
        }
        
        .modal-form select {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }
        
        .modal-form button {
            background: #3490dc;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Applications for: <?php echo htmlspecialchars($job['title']); ?></h1>
            <a href="dashboard.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($applications->num_rows > 0): ?>
            <div class="applications-section">
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
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
                                <td><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($application['application_status']); ?>">
                                        <?php echo ucfirst($application['application_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-primary view-details" data-id="<?php echo $application['application_id']; ?>">
                                            View Details
                                        </button>
                                        <button class="btn-secondary update-status" data-id="<?php echo $application['application_id']; ?>">
                                            Update Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Application Details Modal Content (Hidden by default) -->
                            <div id="details-<?php echo $application['application_id']; ?>" style="display: none;">
                                <div class="application-detail">
                                    <h3><?php echo htmlspecialchars($application['applicant_name']); ?>'s Application</h3>
                                    
                                    <?php if ($application['cover_letter']): ?>
                                    <div class="detail-section">
                                        <h4>Cover Letter</h4>
                                        <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($application['skills']): ?>
                                    <div class="detail-section">
                                        <h4>Skills</h4>
                                        <p><?php echo nl2br(htmlspecialchars($application['skills'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($application['experience']): ?>
                                    <div class="detail-section">
                                        <h4>Experience</h4>
                                        <p><?php echo nl2br(htmlspecialchars($application['experience'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($application['education']): ?>
                                    <div class="detail-section">
                                        <h4>Education</h4>
                                        <p><?php echo nl2br(htmlspecialchars($application['education'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($application['phone']): ?>
                                    <div class="detail-section">
                                        <h4>Contact</h4>
                                        <p>Phone: <?php echo htmlspecialchars($application['phone']); ?></p>
                                        <p>Email: <?php echo htmlspecialchars($application['applicant_email']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($application['resume_path']): ?>
                                    <div class="detail-section">
                                        <h4>Resume</h4>
                                        <a href="<?php echo htmlspecialchars('../' . $application['resume_path']); ?>" target="_blank" class="btn-primary">
                                            <i class="fas fa-file-pdf"></i> View Resume
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="application-detail">
                <p>No applications have been received for this job yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Update Application Status</h2>
            <form class="modal-form" id="updateStatusForm" method="post" action="update-application-status.php">
                <input type="hidden" name="application_id" id="application_id" value="">
                <select name="status" id="status">
                    <option value="pending">Pending</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="shortlisted">Shortlisted</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button type="submit">Update Status</button>
            </form>
        </div>
    </div>

    <?php include '../includes/bootstrap_footer.php'; ?>

    <script>
        // View Details functionality
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const detailsDiv = document.getElementById(`details-${id}`);
                
                // Toggle visibility of all detail divs
                document.querySelectorAll('[id^="details-"]').forEach(div => {
                    if (div.id === `details-${id}`) {
                        div.style.display = div.style.display === 'none' ? 'block' : 'none';
                    } else {
                        div.style.display = 'none';
                    }
                });
            });
        });
        
        // Update Status Modal functionality
        const modal = document.getElementById('statusModal');
        const closeBtn = document.querySelector('.close');
        
        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('application_id').value = id;
                modal.style.display = 'block';
            });
        });
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
