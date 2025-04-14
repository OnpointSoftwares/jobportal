<?php
require_once 'config/database.php';
session_start();

$conn = getConnection();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get search parameters
$keyword = isset($_GET['keyword']) ? $conn->real_escape_string($_GET['keyword']) : '';
$location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';
$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build WHERE clause
$where = "j.status = 'open' AND j.approval_status = 'approved'";
if ($keyword) {
    $where .= " AND (j.title LIKE '%$keyword%' OR j.description LIKE '%$keyword%')";
}
if ($location) {
    $where .= " AND j.location LIKE '%$location%'";
}
if ($type) {
    $where .= " AND j.type = '$type'";
}
if ($category) {
    $where .= " AND jcr.category_id = $category AND approval_status='approved'" ;
}

// Get jobs with company information
$sql = "SELECT j.*, c.company_name, c.logo_path, GROUP_CONCAT(jc.name) as categories 
        FROM jobs j 
        JOIN company_profiles c ON j.company_id = c.id 
        LEFT JOIN job_category_relations jcr ON j.id = jcr.job_id 
        LEFT JOIN job_categories jc ON jcr.category_id = jc.id 
        WHERE $where 
        GROUP BY j.id 
        ORDER BY j.created_at DESC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT j.id) as total 
              FROM jobs j 
              LEFT JOIN job_category_relations jcr ON j.id = jcr.job_id 
              WHERE $where";
$count_result = $conn->query($count_sql);
$total_jobs = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $limit);

// Get job categories for filter
$categories_sql = "SELECT * FROM job_categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .job-card {
            transition: transform 0.3s;
        }
        .job-card:hover {
            transform: translateY(-5px);
        }
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .category-badge {
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include("includes/bootstrap_header.php"); ?>


    <!-- Search Section -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <h1 class="display-4 mb-4">Find Your Perfect Job</h1>
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="keyword" placeholder="Job title or keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="location" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Job Types</option>
                        <option value="full-time" <?php echo $type == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                        <option value="part-time" <?php echo $type == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                        <option value="contract" <?php echo $type == 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="internship" <?php echo $type == 'internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light w-100">Search</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Categories</h5>
                            <div class="d-grid gap-2">
                                <a href="?keyword=<?php echo urlencode($keyword); ?>&location=<?php echo urlencode($location); ?>&type=<?php echo urlencode($type); ?>" 
                                   class="btn btn-outline-primary btn-sm <?php echo !$category ? 'active' : ''; ?>">
                                    All Categories
                                </a>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <a href="?keyword=<?php echo urlencode($keyword); ?>&location=<?php echo urlencode($location); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo $cat['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="col-md-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4><?php echo $total_jobs; ?> Jobs Found</h4>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Newest First</a></li>
                                <li><a class="dropdown-item" href="#">Oldest First</a></li>
                            </ul>
                        </div>
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <?php while($job = $result->fetch_assoc()): ?>
                            <div class="card job-card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <img src="<?php echo $job['logo_path'] ? htmlspecialchars($job['logo_path']) : 'images/default-company.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                                 class="company-logo mb-2">
                                        </div>
                                        <div class="col-md-7">
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($job['company_name']); ?> 
                                                <i class="fas fa-map-marker-alt ms-3 me-1"></i> <?php echo htmlspecialchars($job['location']); ?>
                                            </p>
                                            <p class="card-text"><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
                                            <?php if($job['categories']): ?>
                                                <div class="mb-2">
                                                    <?php foreach(explode(',', $job['categories']) as $cat): ?>
                                                        <span class="badge bg-light text-dark category-badge"><?php echo htmlspecialchars($cat); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($job['type']); ?></span>
                                            <?php if($job['salary_range']): ?>
                                                <p class="text-success mb-2"><?php echo htmlspecialchars($job['salary_range']); ?></p>
                                            <?php endif; ?>
                                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No jobs found matching your criteria</h5>
                            <p>Try adjusting your search filters or browse all available jobs</p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Jobs pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>&location=<?php echo urlencode($location); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo $category; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

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
