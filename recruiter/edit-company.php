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

// Get existing company profile if any
$company_sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$company_stmt = $conn->prepare($company_sql);
$company_stmt->bind_param("i", $_SESSION['user_id']);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'];
    $description = $_POST['description'];
    $industry = $_POST['industry'];
    $location = $_POST['location'];
    $website = $_POST['website'];

    // Handle logo upload
    $logo_path = $company['logo_path'] ?? null; // Keep existing logo if no new one uploaded
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/company_logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                $logo_path = 'uploads/company_logos/' . $new_filename;
            }
        }
    }

    try {
        if ($company) {
            // Update existing profile
            $sql = "UPDATE company_profiles 
                   SET company_name = ?, description = ?, industry = ?, website = ?, 
                       location = ?, logo_path = ?
                   WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", 
                $company_name, 
                $description, 
                $industry, 
                $website, 
                $location, 
                $logo_path,
                $_SESSION['user_id']
            );
        } else {
            // Create new profile
            $sql = "INSERT INTO company_profiles 
                   (user_id, company_name, description, industry, website, location, logo_path)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssss", 
                $_SESSION['user_id'],
                $company_name, 
                $description, 
                $industry, 
                $website, 
                $location,
                $logo_path
            );
        }

        if ($stmt->execute()) {
            $success = "Company profile " . ($company ? "updated" : "created") . " successfully!";
            header("refresh:2;url=dashboard.php");
        } else {
            throw new Exception("Error saving company profile");
        }
    } catch (Exception $e) {
        $error = "Error saving company profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $company ? 'Edit' : 'Create'; ?> Company Profile - Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
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

        .logo-preview {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            margin-top: 1rem;
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
    <?php include '../includes/admin_header.php'; ?>

    <div class="profile-container">
        <h1 style="margin-bottom: 2rem;"><?php echo $company ? 'Edit' : 'Create'; ?> Company Profile</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <form action="edit-company.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="company_name">Company Name*</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Company Description*</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="industry">Industry*</label>
                    <input type="text" id="industry" name="industry" 
                           value="<?php echo htmlspecialchars($company['industry'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="location">Location*</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" 
                           value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>" 
                           placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="logo">Company Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/*" onchange="previewLogo(this)">
                    <?php if (isset($company['logo_path'])): ?>
                        <img src="<?php echo htmlspecialchars('../' . $company['logo_path']); ?>" 
                             alt="Current company logo" class="logo-preview" id="logoPreview">
                    <?php else: ?>
                        <img src="../images/default-company-logo.png" 
                             alt="Logo preview" class="logo-preview" id="logoPreview" style="display: none;">
                    <?php endif; ?>
                </div>

                <button type="submit" class="submit-btn">
                    <?php echo $company ? 'Update Profile' : 'Create Profile'; ?>
                </button>
            </form>
        </div>
    </div>

    <?php include '../includes/bootstrap_footer.php'; ?>

    <script>
        function previewLogo(input) {
            const preview = document.getElementById('logoPreview');
            preview.style.display = 'block';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const companyName = document.getElementById('company_name').value.trim();
            const description = document.getElementById('description').value.trim();
            const industry = document.getElementById('industry').value.trim();
            const location = document.getElementById('location').value.trim();

            if (!companyName || !description || !industry || !location) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
