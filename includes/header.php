<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['name']); ?> - <?php echo htmlspecialchars($profile['job_title']); ?></title>
    <meta name="description" content="Portfolio of <?php echo htmlspecialchars($profile['name']); ?>, <?php echo htmlspecialchars($profile['job_title']); ?> - <?php echo htmlspecialchars(substr($profile['summary'], 0, 150)); ?>...">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Print-specific CSS -->
    <link rel="stylesheet" href="css/print.css" media="print">
</head>
<body>
    <header class="py-3">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#"><?php 
                        // Extract first name from full name
                        $firstName = explode(' ', $profile['name'])[0];
                        echo htmlspecialchars($firstName); 
                    ?></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link active" href="#profile"><i class="fas fa-user me-1"></i>Profile</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#experience"><i class="fas fa-briefcase me-1"></i>Experience</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#education"><i class="fas fa-graduation-cap me-1"></i>Education</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#skills">Skills</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#achievements"><i class="fas fa-award me-1"></i>Achievements</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#projects"><i class="fas fa-code me-1"></i>Projects</a>
                            </li>
                            <li class="nav-item d-none d-print-none">
                                <button class="btn btn-print ms-2" aria-label="Print Resume"><i class="fas fa-print me-1"></i>Print</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <main class="container my-4">
        <!-- Main content sections will be included here -->
    </main>
</body>
</html>
