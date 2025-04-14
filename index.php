<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPortal - Find Your Dream Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            border: none;
            transition: transform 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .search-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
    </style>
    
</head>
<body>
    <!-- Navigation -->
  <?php include("includes/bootstrap_header.php"); ?>


    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 mb-4">Find Your Dream Job Today</h1>
                    <p class="lead mb-5">Discover thousands of job opportunities with the best companies</p>
                    <div class="search-form">
                        <form action="jobs.php" method="GET" class="row g-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="keyword" placeholder="Job title or keyword">
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="location" placeholder="Location">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Jobs Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Jobs</h2>
            <div class="row">
                <?php
                require_once 'config/database.php';
                $conn = getConnection();
                
                $sql = "SELECT j.*, c.company_name, c.logo_path 
                        FROM jobs j 
                        JOIN company_profiles c ON j.company_id = c.id 
                        WHERE j.status = 'open' 
                        ORDER BY j.created_at DESC LIMIT 6";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="col-md-4 mb-4">
                            <div class="card feature-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="' . ($row['logo_path'] ? $row['logo_path'] : 'images/default-company.png') . '" 
                                             alt="' . htmlspecialchars($row['company_name']) . '" 
                                             class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <h5 class="card-title mb-0">' . htmlspecialchars($row['title']) . '</h5>
                                            <small class="text-muted">' . htmlspecialchars($row['company_name']) . '</small>
                                        </div>
                                    </div>
                                    <p class="card-text">' . htmlspecialchars(substr($row['description'], 0, 100)) . '...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">' . htmlspecialchars($row['type']) . '</span>
                                        <a href="job-details.php?id=' . $row['id'] . '" class="btn btn-outline-primary btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="col-12 text-center">
                            <p>No jobs available at the moment.</p>
                          </div>';
                }
                $conn->close();
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="jobs.php" class="btn btn-primary">View All Jobs</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Us</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-search fa-3x mb-3 text-primary"></i>
                            <h4>Easy Job Search</h4>
                            <p>Find the perfect job match with our advanced search filters</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-building fa-3x mb-3 text-primary"></i>
                            <h4>Top Companies</h4>
                            <p>Connect with leading companies across various industries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-mobile-alt fa-3x mb-3 text-primary"></i>
                            <h4>Mobile Friendly</h4>
                            <p>Access job opportunities anytime, anywhere</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php
    include('includes/bootstrap_footer.php');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
