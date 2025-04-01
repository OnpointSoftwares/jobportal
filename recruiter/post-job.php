<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$conn = getConnection();
$error = '';
$success = '';

// Get company profile
$company_sql = "SELECT id FROM company_profiles WHERE user_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $_SESSION['user_id']);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();

if (!$company) {
    header("Location: company-profile.php");
    exit();
}

// Get job categories
$categories_sql = "SELECT id, name FROM job_categories ORDER BY name";
$categories = $conn->query($categories_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $location = $_POST['location'];
    $type = $_POST['type'];
    $salary_range = $_POST['salary_range'];
    $selected_categories = $_POST['categories'] ?? [];

    try {
        $conn->begin_transaction();

        // Insert job
        $job_sql = "INSERT INTO jobs (company_id, title, description, requirements, location, type, salary_range) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $job_stmt = $conn->prepare($job_sql);
        $job_stmt->bind_param("issssss", 
            $company['id'], 
            $title, 
            $description, 
            $requirements, 
            $location, 
            $type, 
            $salary_range
        );
        
        if ($job_stmt->execute()) {
            $job_id = $conn->insert_id;

            // Insert job categories
            if (!empty($selected_categories)) {
                $category_sql = "INSERT INTO job_category_relations (job_id, category_id) VALUES (?, ?)";
                $category_stmt = $conn->prepare($category_sql);
                
                foreach ($selected_categories as $category_id) {
                    $category_stmt->bind_param("ii", $job_id, $category_id);
                    $category_stmt->execute();
                }
            }

            $conn->commit();
            $success = "Job posted successfully!";
            header("refresh:2;url=dashboard.php");
        } else {
            throw new Exception("Error posting job");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error posting job: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .post-job-container {
            max-width: 800px;
            margin: 8rem auto 4rem;
            padding: 0 20px;
        }

        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .category-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-checkbox input[type="checkbox"] {
            width: auto;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: var(--secondary-color);
        }

        .error-message {
            color: #dc2626;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success-message {
            color: #059669;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="post-job-container">
        <h1 style="margin-bottom: 2rem;">Post New Job</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <form action="post-job.php" method="POST">
                <div class="form-group">
                    <label for="title">Job Title*</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Job Description*</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="requirements">Requirements*</label>
                    <textarea id="requirements" name="requirements" required></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location*</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label for="type">Employment Type*</label>
                    <select id="type" name="type" required>
                        <option value="">Select type</option>
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="internship">Internship</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="salary_range">Salary Range</label>
                    <input type="text" id="salary_range" name="salary_range" placeholder="e.g. $50,000 - $70,000">
                </div>

                <div class="form-group">
                    <label>Job Categories</label>
                    <div class="categories-grid">
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <div class="category-checkbox">
                                <input type="checkbox" id="category_<?php echo $category['id']; ?>" 
                                       name="categories[]" value="<?php echo $category['id']; ?>">
                                <label for="category_<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Post Job</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const requirements = document.getElementById('requirements').value.trim();
            const location = document.getElementById('location').value.trim();
            const type = document.getElementById('type').value;

            if (!title || !description || !requirements || !location || !type) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });

        // Rich text editor functionality could be added here
    </script>
</body>
</html>
<?php
$conn->close();
?>
