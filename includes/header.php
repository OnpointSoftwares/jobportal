<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="container">
        <div class="logo">
            <a href="index.php" style="text-decoration: none;">
                <h1>JobPortal</h1>
            </a>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
            <li><a href="jobs.php" <?php echo $current_page == 'jobs.php' ? 'class="active"' : ''; ?>>Jobs</a></li>
            <li><a href="companies.php" <?php echo $current_page == 'companies.php' ? 'class="active"' : ''; ?>>Companies</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_type'] == 'recruiter'): ?>
                    <li><a href="../recruiter/dashboard.php">Dashboard</a></li>
                <?php elseif ($_SESSION['user_type'] == 'jobseeker'): ?>
                    <li><a href="../jobseeker/dashboard.php">Dashboard</a></li>
                <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
                    <li><a href="../admin/dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-login">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn-login" <?php echo $current_page == 'login.php' ? 'class="active"' : ''; ?>>Login</a></li>
                <li><a href="register.php" class="btn-register" <?php echo $current_page == 'register.php' ? 'class="active"' : ''; ?>>Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
