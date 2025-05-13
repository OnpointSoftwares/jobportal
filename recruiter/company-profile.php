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

// Get company's active jobs count
$jobs_count = 0;
$applications_count = 0;

if ($company) {
    $jobs_sql = "SELECT COUNT(*) as count FROM jobs WHERE company_id = ? AND status = 'open'";
    $jobs_stmt = $conn->prepare($jobs_sql);
    $jobs_stmt->bind_param("i", $company['id']);
    $jobs_stmt->execute();
    $jobs_count = $jobs_stmt->get_result()->fetch_assoc()['count'];
    
    // Get total applications count
    $applications_sql = "SELECT COUNT(*) as count 
                        FROM job_applications ja 
                        JOIN jobs j ON ja.job_id = j.id 
                        WHERE j.company_id = ?";
    $applications_stmt = $conn->prepare($applications_sql);
    $applications_stmt->bind_param("i", $company['id']);
    $applications_stmt->execute();
    $applications_count = $applications_stmt->get_result()->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .company-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .company-logo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 4px solid white;
            margin-bottom: 1.5rem;
        }
        
        .edit-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.3s;
        }
        
        .edit-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .company-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .info-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .info-card h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            font-size: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .info-card h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-item strong {
            display: block;
            margin-bottom: 0.3rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .no-profile {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .no-profile i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .no-profile h2 {
            margin-bottom: 1rem;
            color: #374151;
        }
        
        .no-profile p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .create-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .create-btn:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="profile-container">
        <h1 style="margin-bottom: 2rem;">Company Profile</h1>
        
        <?php if ($company): ?>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="number"><?php echo $jobs_count; ?></div>
                    <div class="label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $applications_count; ?></div>
                    <div class="label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="number">
                        <?php 
                            // Calculate days since profile creation
                            $created = new DateTime($company['created_at']);
                            $now = new DateTime();
                            $interval = $created->diff($now);
                            echo $interval->days;
                        ?>
                    </div>
                    <div class="label">Days Active</div>
                </div>
            </div>
            
            <div class="company-header">
                <img src="<?php echo "../" . ($company['logo_path'] ? htmlspecialchars($company['logo_path']) : 'images/default-company-logo.png'); ?>" 
                     alt="<?php echo htmlspecialchars($company['company_name']); ?>" 
                     class="company-logo">
                <h2 style="font-size: 2rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($company['company_name']); ?></h2>
                <p>
                    <i class="fas fa-industry me-2"></i><?php echo htmlspecialchars($company['industry']); ?>
                    <i class="fas fa-map-marker-alt ms-3 me-2"></i><?php echo htmlspecialchars($company['location']); ?>
                </p>
                <a href="edit-company.php" class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
            
            <div class="company-info">
                <div class="info-card">
                    <h2>About Us</h2>
                    <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                </div>
                
                <div class="info-card">
                    <h2>Company Details</h2>
                    <div class="info-item">
                        <strong>Industry</strong>
                        <?php echo htmlspecialchars($company['industry']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Location</strong>
                        <?php echo htmlspecialchars($company['location']); ?>
                    </div>
                    <?php if ($company['website']): ?>
                    <div class="info-item">
                        <strong>Website</strong>
                        <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($company['website']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <strong>Profile Created</strong>
                        <?php echo date('F j, Y', strtotime($company['created_at'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Last Updated</strong>
                        <?php echo date('F j, Y', strtotime($company['updated_at'])); ?>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; text-align: center;">
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
                <a href="../company-details.php?id=<?php echo $company['id']; ?>" class="btn-primary" style="margin-left: 1rem;">
                    <i class="fas fa-eye me-2"></i> View Public Profile
                </a>
            </div>
            
        <?php else: ?>
            <div class="no-profile">
                <i class="fas fa-building"></i>
                <h2>No Company Profile Found</h2>
                <p>You haven't created a company profile yet. Create one to start posting jobs and receiving applications.</p>
                <a href="edit-company.php" class="create-btn">
                    <i class="fas fa-plus-circle"></i> Create Company Profile
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/bootstrap_footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
