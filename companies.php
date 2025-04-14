<?php
require_once 'config/database.php';
session_start();

$conn = getConnection();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$industry = isset($_GET['industry']) ? $conn->real_escape_string($_GET['industry']) : '';

$where = "1=1";
if ($search) {
    $where .= " AND (company_name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($industry) {
    $where .= " AND industry = '$industry'";
}

$sql = "SELECT * FROM company_profiles WHERE $where ORDER BY company_name LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$count_sql = "SELECT COUNT(*) as total FROM company_profiles WHERE $where";
$count_result = $conn->query($count_sql);
$total_companies = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_companies / $limit);

// Get unique industries for filter
$industries_sql = "SELECT DISTINCT industry FROM company_profiles WHERE industry IS NOT NULL ORDER BY industry";
$industries_result = $conn->query($industries_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .company-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .company-card:hover {
            transform: translateY(-5px);
        }
        .company-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
   <?php include("includes/bootstrap_header.php"); ?>

    <!-- Header -->
    <header class="bg-primary text-white py-5">
        <div class="container">
            <h1 class="display-4">Explore Companies</h1>
            <p class="lead">Discover great companies hiring now</p>
        </div>
    </header>

    <!-- Search and Filter Section -->
    <section class="py-4 bg-light">
        <div class="container">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Search companies..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="industry">
                        <option value="">All Industries</option>
                        <?php while($ind = $industries_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($ind['industry']); ?>" 
                                    <?php echo $industry == $ind['industry'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ind['industry']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Companies Grid -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($company = $result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card company-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo $company['logo_path'] ? htmlspecialchars($company['logo_path']) : 'images/default-company.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($company['company_name']); ?>" 
                                             class="company-logo me-3">
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($company['company_name']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($company['industry']); ?></small>
                                        </div>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($company['description'], 0, 150)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if($company['website']): ?>
                                            <a href="<?php echo htmlspecialchars($company['website']); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-globe"></i> Website
                                            </a>
                                        <?php endif; ?>
                                        <a href="company-details.php?id=<?php echo $company['id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No companies found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Companies pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&industry=<?php echo urlencode($industry); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php
    include('includes/bootstrap_footer.php');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
