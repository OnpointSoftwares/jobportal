<?php
require_once 'config/database.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Not Found - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .not-found-container {
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }
        
        .not-found-icon {
            font-size: 6rem;
            color: #3490dc;
            margin-bottom: 1.5rem;
        }
        
        .not-found-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
        }
        
        .not-found-message {
            font-size: 1.2rem;
            color: #4a5568;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        @media (max-width: 576px) {
            .not-found-icon {
                font-size: 4rem;
            }
            
            .not-found-title {
                font-size: 1.8rem;
            }
            
            .not-found-message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include("includes/bootstrap_header.php"); ?>

    <!-- Not Found Content -->
    <div class="container not-found-container">
        <div class="not-found-icon">
            <i class="fas fa-building"></i>
        </div>
        <h1 class="not-found-title">Company Profile Not Found</h1>
        <p class="not-found-message">
            We couldn't find the company profile you're looking for. The company may have removed their profile or the URL might be incorrect.
        </p>
        <div class="action-buttons">
            <a href="companies.php" class="btn btn-primary">
                <i class="fas fa-building me-2"></i>Browse Companies
            </a>
            <a href="jobs.php" class="btn btn-outline-primary">
                <i class="fas fa-briefcase me-2"></i>View Job Listings
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>

    <!-- Footer -->
    <?php include("includes/bootstrap_footer.php"); ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
