<?php
// Admin page for managing the database content
session_start();

// Include the database connection
require_once 'includes/db_connect.php';

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication logic using database
$is_logged_in = false;
$login_error = '';
$password_message = '';

if (isset($_POST['login'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM admin_settings WHERE username = ?');
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Backward compatible authentication - supports both plain text (for migration) and hashed passwords
    $passwordValid = false;
    
    if ($user) {
        // Check if password is hashed (bcrypt hashes start with $2y$)
        if (password_get_info($user['password'])['algo']) {
            // Password is hashed, use password_verify
            $passwordValid = password_verify($_POST['password'], $user['password']);
        } else {
            // Password is plain text (legacy), use direct comparison
            $passwordValid = ($_POST['password'] === $user['password']);
            
            // Auto-migrate plain text password to hashed version on successful login
            if ($passwordValid) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE admin_settings SET password = ? WHERE id = ?');
                $updateStmt->execute([$hashedPassword, $user['id']]);
                error_log("Auto-migrated plain text password to hashed format for user: " . $user['username']);
            }
        }
    }
    
    if ($passwordValid) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity'] = time(); // Add session timeout tracking
        $is_logged_in = true;
        
        // Log successful login
        error_log("Admin login successful for user: " . $user['username'] . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        $login_error = "Invalid credentials";
        
        // Log failed login attempt
        error_log("Failed admin login attempt for username: " . ($_POST['username'] ?? 'unknown') . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        header('Location: admin.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
    $is_logged_in = true;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Process form submissions
$message = '';
if ($is_logged_in) {
    $pdo = getDbConnection();
    
    // Validate CSRF token for all POST requests (except login)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            error_log("CSRF token validation failed for admin user: " . ($_SESSION['admin_username'] ?? 'unknown'));
            http_response_code(403);
            die('Security validation failed. Please refresh the page and try again.');
        }
    }
    
    // Change Theme
    if (isset($_POST['change_theme'])) {
        $theme = filter_var($_POST['theme'], FILTER_SANITIZE_STRING);
        if (in_array($theme, ['light', 'dark', 'blue', 'green'])) { // Whitelist allowed themes
            $stmt = $pdo->prepare('UPDATE admin_settings SET theme = ? WHERE id = 1');
            $stmt->execute([$theme]);
            $message = "Theme updated successfully!";
        } else {
            $message = "Invalid theme selected.";
        }
    }
    
    // Also handle the new save_theme button name
    if (isset($_POST['save_theme'])) {
        $theme = filter_var($_POST['theme'], FILTER_SANITIZE_STRING);
        if (in_array($theme, ['light', 'dark', 'blue', 'green'])) { // Whitelist allowed themes
            $stmt = $pdo->prepare('UPDATE admin_settings SET theme = ? WHERE id = 1');
            $stmt->execute([$theme]);
            $message = "Theme updated successfully!";
        } else {
            $message = "Invalid theme selected.";
        }
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $stmt = $pdo->prepare('SELECT * FROM admin_settings WHERE id = 1');
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Backward compatible current password verification
        $currentPasswordValid = false;
        if (password_get_info($admin['password'])['algo']) {
            // Password is hashed, use password_verify
            $currentPasswordValid = password_verify($_POST['current_password'], $admin['password']);
        } else {
            // Password is plain text (legacy), use direct comparison
            $currentPasswordValid = ($_POST['current_password'] === $admin['password']);
        }
        
        if ($currentPasswordValid) {
            if ($_POST['new_password'] == $_POST['confirm_password']) {
                if (strlen($_POST['new_password']) >= 8) { // Increased minimum length
                    // Hash the new password
                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE admin_settings SET password = ? WHERE id = 1');
                    $stmt->execute([$hashedPassword]);
                    $password_message = "Password updated successfully! Please log in again.";
                    
                    // Log password change
                    error_log("Admin password changed for user: " . ($_SESSION['admin_username'] ?? 'unknown'));
                    
                    // Force re-login for security
                    session_destroy();
                    header('Location: admin.php?password_changed=1');
                    exit;
                } else {
                    $password_message = "New password must be at least 8 characters long.";
                }
            } else {
                $password_message = "New password and confirmation do not match.";
            }
        } else {
            $password_message = "Current password is incorrect.";
        }
    }
    
    // Update profile
    if (isset($_POST['save_profile'])) {
        $stmt = $pdo->prepare('UPDATE profile SET 
            name = ?, 
            job_title = ?, 
            summary = ?, 
            email = ?, 
            phone = ?, 
            location = ?, 
            linkedin = ?, 
            website = ?, 
            github = ?
            WHERE id = 1');
            
        $stmt->execute([
            $_POST['profile']['name'],
            $_POST['profile']['job_title'],
            $_POST['profile']['summary'],
            $_POST['profile']['email'],
            $_POST['profile']['phone'],
            $_POST['profile']['location'],
            $_POST['profile']['linkedin'],
            $_POST['profile']['website'],
            $_POST['profile']['github']
        ]);
        
        $message = "Profile updated successfully!";
    }
    
    // Handle experience operations
    if (isset($_POST['add_experience'])) {
        $description = json_encode($_POST['experience']['description_items'] ?? []);
        
        $stmt = $pdo->prepare('INSERT INTO experience 
            (job_title, company, start_date, end_date, location, description) 
            VALUES (?, ?, ?, ?, ?, ?)');
            
        $stmt->execute([
            $_POST['experience']['job_title'],
            $_POST['experience']['company'],
            $_POST['experience']['start_date'],
            $_POST['experience']['end_date'],
            $_POST['experience']['location'],
            $description
        ]);
        
        $message = "Experience added successfully!";
    }
    
    if (isset($_POST['update_experience'])) {
        $description = json_encode($_POST['experience']['description_items'] ?? []);
        
        $stmt = $pdo->prepare('UPDATE experience SET 
            job_title = ?, 
            company = ?, 
            start_date = ?, 
            end_date = ?, 
            location = ?, 
            description = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['experience']['job_title'],
            $_POST['experience']['company'],
            $_POST['experience']['start_date'],
            $_POST['experience']['end_date'],
            $_POST['experience']['location'],
            $description,
            $_POST['experience']['id']
        ]);
        
        $message = "Experience updated successfully!";
    }
    
    if (isset($_POST['delete_experience'])) {
        $stmt = $pdo->prepare('DELETE FROM experience WHERE id = ?');
        $stmt->execute([$_POST['experience_id']]);
        
        $message = "Experience deleted successfully!";
    }
    
    // Handle education operations
    if (isset($_POST['add_education'])) {
        $description = json_encode($_POST['education']['description_items'] ?? []);
        
        $stmt = $pdo->prepare('INSERT INTO education 
            (degree, institution, start_date, end_date, location, description) 
            VALUES (?, ?, ?, ?, ?, ?)');
            
        $stmt->execute([
            $_POST['education']['degree'],
            $_POST['education']['institution'],
            $_POST['education']['start_date'],
            $_POST['education']['end_date'],
            $_POST['education']['location'],
            $description
        ]);
        
        $message = "Education added successfully!";
    }
    
    if (isset($_POST['update_education'])) {
        $description = json_encode($_POST['education']['description_items'] ?? []);
        
        $stmt = $pdo->prepare('UPDATE education SET 
            degree = ?, 
            institution = ?, 
            start_date = ?, 
            end_date = ?, 
            location = ?, 
            description = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['education']['degree'],
            $_POST['education']['institution'],
            $_POST['education']['start_date'],
            $_POST['education']['end_date'],
            $_POST['education']['location'],
            $description,
            $_POST['education']['id']
        ]);
        
        $message = "Education updated successfully!";
    }
    
    if (isset($_POST['delete_education'])) {
        $stmt = $pdo->prepare('DELETE FROM education WHERE id = ?');
        $stmt->execute([$_POST['education_id']]);
        
        $message = "Education deleted successfully!";
    }
    
    // Handle certificates and conferences operations
    if (isset($_POST['add_certificate_conference'])) {
        $stmt = $pdo->prepare('INSERT INTO certificates_conferences 
            (title, description, date, type, issuer, url) 
            VALUES (?, ?, ?, ?, ?, ?)');
            
        $stmt->execute([
            $_POST['certificate_conference']['title'],
            $_POST['certificate_conference']['description'],
            $_POST['certificate_conference']['date'],
            $_POST['certificate_conference']['type'],
            $_POST['certificate_conference']['issuer'],
            $_POST['certificate_conference']['url']
        ]);
        
        $message = "Certificate/Conference added successfully!";
    }
    
    if (isset($_POST['update_certificate_conference'])) {
        $stmt = $pdo->prepare('UPDATE certificates_conferences SET 
            title = ?, 
            description = ?, 
            date = ?, 
            type = ?, 
            issuer = ?, 
            url = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['certificate_conference']['title'],
            $_POST['certificate_conference']['description'],
            $_POST['certificate_conference']['date'],
            $_POST['certificate_conference']['type'],
            $_POST['certificate_conference']['issuer'],
            $_POST['certificate_conference']['url'],
            $_POST['certificate_conference']['id']
        ]);
        
        $message = "Certificate/Conference updated successfully!";
    }
    
    if (isset($_POST['delete_certificate_conference'])) {
        $stmt = $pdo->prepare('DELETE FROM certificates_conferences WHERE id = ?');
        $stmt->execute([$_POST['certificate_conference_id']]);
        
        $message = "Certificate/Conference deleted successfully!";
    }
    
    // Handle skills operations
    if (isset($_POST['add_skill'])) {
        $stmt = $pdo->prepare('INSERT INTO skills 
            (category, name, level) 
            VALUES (?, ?, ?)');
            
        $stmt->execute([
            $_POST['skill']['category'],
            $_POST['skill']['name'],
            $_POST['skill']['level']
        ]);
        
        $message = "Skill added successfully!";
    }
    
    if (isset($_POST['update_skill'])) {
        $stmt = $pdo->prepare('UPDATE skills SET 
            category = ?, 
            name = ?, 
            level = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['skill']['category'],
            $_POST['skill']['name'],
            $_POST['skill']['level'],
            $_POST['skill']['id']
        ]);
        
        $message = "Skill updated successfully!";
    }
    
    if (isset($_POST['delete_skill'])) {
        $stmt = $pdo->prepare('DELETE FROM skills WHERE id = ?');
        $stmt->execute([$_POST['skill_id']]);
        
        $message = "Skill deleted successfully!";
    }
    
    // Handle achievements operations
    if (isset($_POST['add_achievement'])) {
        $stmt = $pdo->prepare('INSERT INTO achievements 
            (title, description, date) 
            VALUES (?, ?, ?)');
            
        $stmt->execute([
            $_POST['achievement']['title'],
            $_POST['achievement']['description'],
            $_POST['achievement']['date']
        ]);
        
        $message = "Achievement added successfully!";
    }
    
    if (isset($_POST['update_achievement'])) {
        $stmt = $pdo->prepare('UPDATE achievements SET 
            title = ?, 
            description = ?, 
            date = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['achievement']['title'],
            $_POST['achievement']['description'],
            $_POST['achievement']['date'],
            $_POST['achievement']['id']
        ]);
        
        $message = "Achievement updated successfully!";
    }
    
    if (isset($_POST['delete_achievement'])) {
        $stmt = $pdo->prepare('DELETE FROM achievements WHERE id = ?');
        $stmt->execute([$_POST['achievement_id']]);
        
        $message = "Achievement deleted successfully!";
    }
    
    // Handle projects operations
    if (isset($_POST['add_project'])) {
        $technologies = json_encode($_POST['project']['technologies'] ?? []);
        
        $stmt = $pdo->prepare('INSERT INTO projects 
            (title, description, technologies, link, image) 
            VALUES (?, ?, ?, ?, ?)');
            
        $stmt->execute([
            $_POST['project']['title'],
            $_POST['project']['description'],
            $technologies,
            $_POST['project']['link'],
            $_POST['project']['image']
        ]);
        
        $message = "Project added successfully!";
    }
    
    if (isset($_POST['update_project'])) {
        $technologies = json_encode($_POST['project']['technologies'] ?? []);
        
        $stmt = $pdo->prepare('UPDATE projects SET 
            title = ?, 
            description = ?, 
            technologies = ?, 
            link = ?, 
            image = ?
            WHERE id = ?');
            
        $stmt->execute([
            $_POST['project']['title'],
            $_POST['project']['description'],
            $technologies,
            $_POST['project']['link'],
            $_POST['project']['image'],
            $_POST['project']['id']
        ]);
        
        $message = "Project updated successfully!";
    }
    
    if (isset($_POST['delete_project'])) {
        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$_POST['project_id']]);
        
        $message = "Project deleted successfully!";
    }
}

// Get current data if logged in
if ($is_logged_in) {
    $pdo = getDbConnection();
    
    // Fetch profile
    $stmt = $pdo->prepare('SELECT * FROM profile WHERE id = 1');
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch other data as needed
    $stmt = $pdo->query('SELECT * FROM experience ORDER BY start_date DESC');
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query('SELECT * FROM education ORDER BY end_date DESC');
    $educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query('SELECT * FROM skills ORDER BY category, name');
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query('SELECT * FROM achievements ORDER BY date DESC');
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query('SELECT * FROM projects');
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current theme
    try {
        $stmt = $pdo->prepare('SELECT theme FROM admin_settings WHERE id = 1');
        $stmt->execute();
        $current_theme = $stmt->fetchColumn();
        if (!$current_theme) {
            $current_theme = 'light';
        }
    } catch (PDOException $e) {
        // Theme column might not exist yet
        $current_theme = 'light';
    }

    // Group skills by category
    $skillsByCategory = [];
    foreach ($skills as $skill) {
        $skillsByCategory[$skill['category']][] = $skill;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?php if ($is_logged_in && $current_theme !== 'light'): ?>
    <link rel="stylesheet" href="css/theme-<?php echo htmlspecialchars($current_theme); ?>.css">
    <?php endif; ?>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="m-0">Resume Admin Panel</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!$is_logged_in): ?>
                            <!-- Login Form -->
                            <h3>Login</h3>
                            <?php if (isset($login_error)): ?>
                                <div class="alert alert-danger"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                            <?php if (isset($_GET['password_changed'])): ?>
                                <div class="alert alert-success">Password updated successfully! Please log in with your new password.</div>
                            <?php endif; ?>
                            <?php if (isset($_GET['timeout'])): ?>
                                <div class="alert alert-warning">Your session has expired for security reasons. Please log in again.</div>
                            <?php endif; ?>
                            <form method="post" action="admin.php">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary">Login</button>
                            </form>
                        <?php else: ?>
                            <!-- Admin Dashboard -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3>Welcome, Admin</h3>
                                <a href="admin.php?logout=1" class="btn btn-danger">Logout</a>
                            </div>
                            
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success"><?php echo $message; ?></div>
                            <?php endif; ?>
                            
                            <!-- Navigation tabs -->
                            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="true">Profile</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="experience-tab" data-bs-toggle="tab" data-bs-target="#experience-tab-pane" type="button" role="tab" aria-controls="experience-tab-pane" aria-selected="false">Experience</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education-tab-pane" type="button" role="tab" aria-controls="education-tab-pane" aria-selected="false">Education</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills-tab-pane" type="button" role="tab" aria-controls="skills-tab-pane" aria-selected="false">Skills</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements-tab-pane" type="button" role="tab" aria-controls="achievements-tab-pane" aria-selected="false">Achievements</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="certificates-conferences-tab" data-bs-toggle="tab" data-bs-target="#certificates-conferences-tab-pane" type="button" role="tab" aria-controls="certificates-conferences-tab-pane" aria-selected="false">Certificates & Conferences</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects-tab-pane" type="button" role="tab" aria-controls="projects-tab-pane" aria-selected="false">Projects</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-tab-pane" type="button" role="tab" aria-controls="settings-tab-pane" aria-selected="false">Settings</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="adminTabsContent">
                                <!-- Profile Tab -->
                                <div class="tab-pane fade show active" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                                    <h4 class="mb-3">Edit Profile</h4>
                                    <form method="post" action="admin.php">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="profile[name]" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="job_title" class="form-label">Job Title</label>
                                            <input type="text" class="form-control" id="job_title" name="profile[job_title]" value="<?php echo htmlspecialchars($profile['job_title']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="summary" class="form-label">Summary</label>
                                            <textarea class="form-control" id="summary" name="profile[summary]" rows="3" required><?php echo htmlspecialchars($profile['summary']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="profile[email]" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="phone" name="profile[phone]" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="location" name="profile[location]" value="<?php echo htmlspecialchars($profile['location']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="linkedin" class="form-label">LinkedIn</label>
                                            <input type="text" class="form-control" id="linkedin" name="profile[linkedin]" value="<?php echo htmlspecialchars($profile['linkedin']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="website" class="form-label">Website</label>
                                            <input type="text" class="form-control" id="website" name="profile[website]" value="<?php echo htmlspecialchars($profile['website']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="github" class="form-label">GitHub</label>
                                            <input type="text" class="form-control" id="github" name="profile[github]" value="<?php echo htmlspecialchars($profile['github']); ?>">
                                        </div>
                                        <button type="submit" name="save_profile" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                                
                                <!-- Experience Tab -->
                                <div class="tab-pane fade" id="experience-tab-pane" role="tabpanel" aria-labelledby="experience-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Experience</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExperienceModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <div class="list-group">
                                        <?php foreach ($experiences as $exp): 
                                            $items = json_decode($exp['description'], true);
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($exp['job_title']); ?></h5>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-experience-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editExperienceModal"
                                                        data-id="<?php echo $exp['id']; ?>"
                                                        data-job-title="<?php echo htmlspecialchars($exp['job_title']); ?>"
                                                        data-company="<?php echo htmlspecialchars($exp['company']); ?>"
                                                        data-start-date="<?php echo htmlspecialchars($exp['start_date']); ?>"
                                                        data-end-date="<?php echo htmlspecialchars($exp['end_date']); ?>"
                                                        data-location="<?php echo htmlspecialchars($exp['location']); ?>"
                                                        data-description='<?php echo json_encode($items); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteExperienceModal"
                                                        data-id="<?php echo $exp['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($exp['job_title'] . ' at ' . $exp['company']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($exp['company']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($exp['start_date']); ?> - <?php echo htmlspecialchars($exp['end_date']); ?></small>
                                            <div class="mt-2">
                                                <strong>Responsibilities:</strong>
                                                <ul>
                                                    <?php if (is_array($items)): foreach ($items as $item): ?>
                                                    <li><?php echo htmlspecialchars($item); ?></li>
                                                    <?php endforeach; endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Education Tab -->
                                <div class="tab-pane fade" id="education-tab-pane" role="tabpanel" aria-labelledby="education-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Education</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEducationModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <div class="list-group">
                                        <?php foreach ($educations as $edu): 
                                            $items = json_decode($edu['description'], true);
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($edu['degree']); ?></h5>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-education-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editEducationModal"
                                                        data-id="<?php echo $edu['id']; ?>"
                                                        data-degree="<?php echo htmlspecialchars($edu['degree']); ?>"
                                                        data-institution="<?php echo htmlspecialchars($edu['institution']); ?>"
                                                        data-start-date="<?php echo htmlspecialchars($edu['start_date']); ?>"
                                                        data-end-date="<?php echo htmlspecialchars($edu['end_date']); ?>"
                                                        data-location="<?php echo htmlspecialchars($edu['location']); ?>"
                                                        data-description='<?php echo json_encode($items); ?>'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteEducationModal"
                                                        data-id="<?php echo $edu['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($edu['degree'] . ' at ' . $edu['institution']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($edu['institution']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($edu['start_date']); ?> - <?php echo htmlspecialchars($edu['end_date']); ?></small>
                                            <?php if (is_array($items) && count($items) > 0): ?>
                                            <div class="mt-2">
                                                <ul>
                                                    <?php foreach ($items as $item): ?>
                                                    <li><?php echo htmlspecialchars($item); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Skills Tab -->
                                <div class="tab-pane fade" id="skills-tab-pane" role="tabpanel" aria-labelledby="skills-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Skills</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <?php foreach ($skillsByCategory as $category => $categorySkills): ?>
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($category); ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <?php foreach ($categorySkills as $skill): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span><?php echo htmlspecialchars($skill['name']); ?> (Level: <?php echo $skill['level']; ?>/5)</span>
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-skill-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editSkillModal"
                                                                data-id="<?php echo $skill['id']; ?>"
                                                                data-category="<?php echo htmlspecialchars($skill['category']); ?>"
                                                                data-name="<?php echo htmlspecialchars($skill['name']); ?>"
                                                                data-level="<?php echo $skill['level']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteSkillModal"
                                                                data-id="<?php echo $skill['id']; ?>"
                                                                data-title="<?php echo htmlspecialchars($skill['name']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Achievements Tab -->
                                <div class="tab-pane fade" id="achievements-tab-pane" role="tabpanel" aria-labelledby="achievements-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Achievements</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <div class="list-group">
                                        <?php foreach ($achievements as $achievement): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($achievement['title']); ?></h5>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-achievement-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editAchievementModal"
                                                        data-id="<?php echo $achievement['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($achievement['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($achievement['description']); ?>"
                                                        data-date="<?php echo htmlspecialchars($achievement['date']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteAchievementModal"
                                                        data-id="<?php echo $achievement['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($achievement['title']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($achievement['date']); ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Certificates & Conferences Tab -->
                                <div class="tab-pane fade" id="certificates-conferences-tab-pane" role="tabpanel" aria-labelledby="certificates-conferences-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Certificates & Conferences</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCertificateConferenceModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <div class="list-group">
                                        <?php 
                                        $stmt = $pdo->query('SELECT * FROM certificates_conferences ORDER BY date DESC');
                                        $certificates_conferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($certificates_conferences as $item): 
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-certificate-conference-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCertificateConferenceModal"
                                                        data-id="<?php echo $item['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                        data-date="<?php echo htmlspecialchars($item['date']); ?>"
                                                        data-type="<?php echo htmlspecialchars($item['type']); ?>"
                                                        data-issuer="<?php echo htmlspecialchars($item['issuer']); ?>"
                                                        data-url="<?php echo htmlspecialchars($item['url']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteCertificateConferenceModal"
                                                        data-id="<?php echo $item['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($item['description']); ?></p>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($item['type']); ?> | 
                                                <?php echo htmlspecialchars($item['issuer']); ?> | 
                                                <?php echo htmlspecialchars($item['date']); ?>
                                            </small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Projects Tab -->
                                <div class="tab-pane fade" id="projects-tab-pane" role="tabpanel" aria-labelledby="projects-tab" tabindex="0">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h4>Manage Projects</h4>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                    
                                    <div class="row">
                                        <?php foreach ($projects as $project): 
                                            $technologies = json_decode($project['technologies'], true);
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-project-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editProjectModal"
                                                                data-id="<?php echo $project['id']; ?>"
                                                                data-title="<?php echo htmlspecialchars($project['title']); ?>"
                                                                data-description="<?php echo htmlspecialchars($project['description']); ?>"
                                                                data-link="<?php echo htmlspecialchars($project['link']); ?>"
                                                                data-image="<?php echo htmlspecialchars($project['image']); ?>"
                                                                data-technologies='<?php echo json_encode($technologies); ?>'>
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteProjectModal"
                                                                data-id="<?php echo $project['id']; ?>"
                                                                data-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <p class="card-text"><?php echo htmlspecialchars($project['description']); ?></p>
                                                    <?php if (!empty($project['link'])): ?>
                                                    <p><a href="<?php echo htmlspecialchars($project['link']); ?>" target="_blank"><?php echo htmlspecialchars($project['link']); ?></a></p>
                                                    <?php endif; ?>
                                                    <?php if (is_array($technologies) && count($technologies) > 0): ?>
                                                    <div class="mt-2">
                                                        <strong>Technologies:</strong>
                                                        <div class="mt-1">
                                                            <?php foreach ($technologies as $tech): ?>
                                                            <span class="badge bg-primary me-1"><?php echo htmlspecialchars($tech); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Settings Tab -->
                                <div class="tab-pane fade" id="settings-tab-pane" role="tabpanel" aria-labelledby="settings-tab" tabindex="0">
                                    <h4 class="mb-3">Admin Settings</h4>
                                    
                                    <?php if (!empty($password_message)): ?>
                                        <div class="alert alert-info"><?php echo $password_message; ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Theme Options</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="">
                                                <div class="mb-3">
                                                    <label for="theme" class="form-label">Select Theme</label>
                                                    <select class="form-select" id="theme" name="theme">
                                                        <option value="light" <?php echo ($current_theme == 'light') ? 'selected' : ''; ?>>Light</option>
                                                        <option value="dark" <?php echo ($current_theme == 'dark') ? 'selected' : ''; ?>>Dark</option>
                                                        <option value="blue" <?php echo ($current_theme == 'blue') ? 'selected' : ''; ?>>Blue</option>
                                                        <option value="green" <?php echo ($current_theme == 'green') ? 'selected' : ''; ?>>Green</option>
                                                        <option value="peach" <?php echo ($current_theme == 'peach') ? 'selected' : ''; ?>>Peach</option>
                                                        <option value="neon" <?php echo ($current_theme == 'neon') ? 'selected' : ''; ?>>Neon</option>
                                                        <option value="minimal" <?php echo ($current_theme == 'minimal') ? 'selected' : ''; ?>>Minimalist</option>
                                                        <option value="watercolor" <?php echo ($current_theme == 'watercolor') ? 'selected' : ''; ?>>Watercolor</option>
                                                        <option value="vscode" <?php echo ($current_theme == 'vscode') ? 'selected' : ''; ?>>VSCode</option>
                                                        <option value="matrix" <?php echo ($current_theme == 'matrix') ? 'selected' : ''; ?>>Matrix</option>
                                                        <option value="retro" <?php echo ($current_theme == 'retro') ? 'selected' : ''; ?>>Retro Computer</option>
                                                        <option value="ubuntu" <?php echo ($current_theme == 'ubuntu') ? 'selected' : ''; ?>>Ubuntu</option>
                                                        <option value="github" <?php echo ($current_theme == 'github') ? 'selected' : ''; ?>>GitHub</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <h6>Theme Preview</h6>
                                                    <div class="p-3 border rounded mb-3" id="themePreview">
                                                        <div class="mb-3">
                                                            <button type="button" class="btn btn-primary me-2">Primary Button</button>
                                                            <button type="button" class="btn btn-secondary">Secondary Button</button>
                                                        </div>
                                                        <p>Current Theme: <span id="currentTheme"><?php echo ucfirst($current_theme); ?></span></p>
                                                        <div class="progress mb-3">
                                                            <div class="progress-bar" role="progressbar" style="width: 75%;" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <button type="submit" name="save_theme" class="btn btn-primary">Save Theme</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="mb-0">Change Password</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="admin.php">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                </div>
                                                <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_logged_in): ?>
    <!-- Add Experience Modal -->
    <div class="modal fade" id="addExperienceModal" tabindex="-1" aria-labelledby="addExperienceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExperienceModalLabel">Add New Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <div class="mb-3">
                            <label for="exp_job_title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="exp_job_title" name="experience[job_title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="exp_company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="exp_company" name="experience[company]" required>
                        </div>
                        <div class="mb-3">
                            <label for="exp_start_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control" id="exp_start_date" name="experience[start_date]" placeholder="MM/YYYY" required>
                        </div>
                        <div class="mb-3">
                            <label for="exp_end_date" class="form-label">End Date</label>
                            <input type="text" class="form-control" id="exp_end_date" name="experience[end_date]" placeholder="MM/YYYY or Present" required>
                        </div>
                        <div class="mb-3">
                            <label for="exp_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="exp_location" name="experience[location]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Responsibilities</label>
                            <div id="responsibilities_container">
                                <!-- Container for responsibilities -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_responsibility_item">
                                <i class="fas fa-plus"></i> Add Responsibility
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_experience" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Experience Modal -->
    <div class="modal fade" id="editExperienceModal" tabindex="-1" aria-labelledby="editExperienceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editExperienceModalLabel">Edit Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" id="edit_exp_id" name="experience[id]">
                        <div class="mb-3">
                            <label for="edit_exp_job_title" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="edit_exp_job_title" name="experience[job_title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_exp_company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="edit_exp_company" name="experience[company]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_exp_start_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control" id="edit_exp_start_date" name="experience[start_date]" placeholder="MM/YYYY" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_exp_end_date" class="form-label">End Date</label>
                            <input type="text" class="form-control" id="edit_exp_end_date" name="experience[end_date]" placeholder="MM/YYYY or Present" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_exp_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_exp_location" name="experience[location]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Responsibilities</label>
                            <div id="edit_responsibilities_container">
                                <!-- Container for responsibilities -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="edit_add_responsibility_item">
                                <i class="fas fa-plus"></i> Add Responsibility
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_experience" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Experience Modal -->
    <div class="modal fade" id="deleteExperienceModal" tabindex="-1" aria-labelledby="deleteExperienceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteExperienceModalLabel">Delete Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the experience: <span id="delete_exp_title" class="fw-bold"></span>?</p>
                    <form method="post" action="admin.php">
                        <input type="hidden" id="delete_exp_id" name="experience_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_experience" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Education Modal -->
    <div class="modal fade" id="deleteEducationModal" tabindex="-1" aria-labelledby="deleteEducationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEducationModalLabel">Delete Education</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the education: <span id="delete_edu_title" class="fw-bold"></span>?</p>
                    <form method="post" action="admin.php">
                        <input type="hidden" id="delete_edu_id" name="education_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_education" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Skill Modal -->
    <div class="modal fade" id="deleteSkillModal" tabindex="-1" aria-labelledby="deleteSkillModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSkillModalLabel">Delete Skill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the skill: <span id="delete_skill_title" class="fw-bold"></span>?</p>
                    <form method="post" action="admin.php">
                        <input type="hidden" id="delete_skill_id" name="skill_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_skill" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Achievement Modal -->
    <div class="modal fade" id="deleteAchievementModal" tabindex="-1" aria-labelledby="deleteAchievementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAchievementModalLabel">Delete Achievement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the achievement: <span id="delete_achievement_title" class="fw-bold"></span>?</p>
                    <form method="post" action="admin.php">
                        <input type="hidden" id="delete_achievement_id" name="achievement_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_achievement" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Project Modal -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProjectModalLabel">Delete Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the project: <span id="delete_project_title" class="fw-bold"></span>?</p>
                    <form method="post" action="admin.php">
                        <input type="hidden" id="delete_project_id" name="project_id">
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_project" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Education Modal -->
    <div class="modal fade" id="addEducationModal" tabindex="-1" aria-labelledby="addEducationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEducationModalLabel">Add New Education</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <div class="mb-3">
                            <label for="edu_degree" class="form-label">Degree</label>
                            <input type="text" class="form-control" id="edu_degree" name="education[degree]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edu_institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="edu_institution" name="education[institution]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edu_start_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control" id="edu_start_date" name="education[start_date]" placeholder="MM/YYYY" required>
                        </div>
                        <div class="mb-3">
                            <label for="edu_end_date" class="form-label">End Date</label>
                            <input type="text" class="form-control" id="edu_end_date" name="education[end_date]" placeholder="MM/YYYY or Present" required>
                        </div>
                        <div class="mb-3">
                            <label for="edu_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edu_location" name="education[location]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Details</label>
                            <div id="education_details_container">
                                <!-- Container for education details -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_education_detail">
                                <i class="fas fa-plus"></i> Add Detail
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_education" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Education Modal -->
    <div class="modal fade" id="editEducationModal" tabindex="-1" aria-labelledby="editEducationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEducationModalLabel">Edit Education</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" id="edit_edu_id" name="education[id]">
                        <div class="mb-3">
                            <label for="edit_edu_degree" class="form-label">Degree</label>
                            <input type="text" class="form-control" id="edit_edu_degree" name="education[degree]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_edu_institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="edit_edu_institution" name="education[institution]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_edu_start_date" class="form-label">Start Date</label>
                            <input type="text" class="form-control" id="edit_edu_start_date" name="education[start_date]" placeholder="MM/YYYY" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_edu_end_date" class="form-label">End Date</label>
                            <input type="text" class="form-control" id="edit_edu_end_date" name="education[end_date]" placeholder="MM/YYYY or Present" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_edu_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_edu_location" name="education[location]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Details</label>
                            <div id="edit_education_details_container">
                                <!-- Container for education details -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="edit_add_education_detail">
                                <i class="fas fa-plus"></i> Add Detail
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_education" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Skill Modal -->
    <div class="modal fade" id="addSkillModal" tabindex="-1" aria-labelledby="addSkillModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSkillModalLabel">Add New Skill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <div class="mb-3">
                            <label for="skill_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="skill_category" name="skill[category]" required>
                        </div>
                        <div class="mb-3">
                            <label for="skill_name" class="form-label">Skill Name</label>
                            <input type="text" class="form-control" id="skill_name" name="skill[name]" required>
                        </div>
                        <div class="mb-3">
                            <label for="skill_level" class="form-label">Level (1-5)</label>
                            <input type="number" class="form-control" id="skill_level" name="skill[level]" min="1" max="5" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_skill" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Skill Modal -->
    <div class="modal fade" id="editSkillModal" tabindex="-1" aria-labelledby="editSkillModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSkillModalLabel">Edit Skill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" id="edit_skill_id" name="skill[id]">
                        <div class="mb-3">
                            <label for="edit_skill_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_skill_category" name="skill[category]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_skill_name" class="form-label">Skill Name</label>
                            <input type="text" class="form-control" id="edit_skill_name" name="skill[name]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_skill_level" class="form-label">Level (1-5)</label>
                            <input type="number" class="form-control" id="edit_skill_level" name="skill[level]" min="1" max="5" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_skill" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Achievement Modal -->
    <div class="modal fade" id="addAchievementModal" tabindex="-1" aria-labelledby="addAchievementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAchievementModalLabel">Add New Achievement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <div class="mb-3">
                            <label for="achievement_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="achievement_title" name="achievement[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="achievement_description" class="form-label">Description</label>
                            <textarea class="form-control" id="achievement_description" name="achievement[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="achievement_date" class="form-label">Date</label>
                            <input type="text" class="form-control" id="achievement_date" name="achievement[date]" placeholder="YYYY or YYYY-YYYY" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_achievement" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Achievement Modal -->
    <div class="modal fade" id="editAchievementModal" tabindex="-1" aria-labelledby="editAchievementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAchievementModalLabel">Edit Achievement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" id="edit_achievement_id" name="achievement[id]">
                        <div class="mb-3">
                            <label for="edit_achievement_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_achievement_title" name="achievement[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_achievement_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_achievement_description" name="achievement[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_achievement_date" class="form-label">Date</label>
                            <input type="text" class="form-control" id="edit_achievement_date" name="achievement[date]" placeholder="YYYY or YYYY-YYYY" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_achievement" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProjectModalLabel">Add New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <div class="mb-3">
                            <label for="project_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="project_title" name="project[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="project_description" class="form-label">Description</label>
                            <textarea class="form-control" id="project_description" name="project[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="project_link" class="form-label">Link</label>
                            <input type="text" class="form-control" id="project_link" name="project[link]" placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label for="project_image" class="form-label">Image Path</label>
                            <input type="text" class="form-control" id="project_image" name="project[image]" placeholder="assets/images/projects/example.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Technologies</label>
                            <div id="technologies_container">
                                <!-- Container for technologies -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_technology_item">
                                <i class="fas fa-plus"></i> Add Technology
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_project" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Project Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" id="edit_project_id" name="project[id]">
                        <div class="mb-3">
                            <label for="edit_project_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_project_title" name="project[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_project_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_project_description" name="project[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_project_link" class="form-label">Link</label>
                            <input type="text" class="form-control" id="edit_project_link" name="project[link]" placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label for="edit_project_image" class="form-label">Image Path</label>
                            <input type="text" class="form-control" id="edit_project_image" name="project[image]" placeholder="assets/images/projects/example.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Technologies</label>
                            <div id="edit_technologies_container">
                                <!-- Container for technologies -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="edit_add_technology_item">
                                <i class="fas fa-plus"></i> Add Technology
                            </button>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_project" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Certificate/Conference Modal -->
    <div class="modal fade" id="addCertificateConferenceModal" tabindex="-1" aria-labelledby="addCertificateConferenceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCertificateConferenceModalLabel">Add New Certificate/Conference</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="admin.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="certificate_conference[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="certificate_conference[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="certificate_conference[date]" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="certificate_conference[type]" required>
                                <option value="certificate">Certificate</option>
                                <option value="conference">Conference</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="issuer" class="form-label">Issuer/Organizer</label>
                            <input type="text" class="form-control" id="issuer" name="certificate_conference[issuer]" required>
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">URL (optional)</label>
                            <input type="url" class="form-control" id="url" name="certificate_conference[url]">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_certificate_conference" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Certificate/Conference Modal -->
    <div class="modal fade" id="editCertificateConferenceModal" tabindex="-1" aria-labelledby="editCertificateConferenceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCertificateConferenceModalLabel">Edit Certificate/Conference</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="admin.php">
                    <input type="hidden" name="certificate_conference[id]" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="certificate_conference[title]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="certificate_conference[description]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="certificate_conference[date]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_type" class="form-label">Type</label>
                            <select class="form-select" id="edit_type" name="certificate_conference[type]" required>
                                <option value="certificate">Certificate</option>
                                <option value="conference">Conference</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_issuer" class="form-label">Issuer/Organizer</label>
                            <input type="text" class="form-control" id="edit_issuer" name="certificate_conference[issuer]" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_url" class="form-label">URL (optional)</label>
                            <input type="url" class="form-control" id="edit_url" name="certificate_conference[url]">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_certificate_conference" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Certificate/Conference Modal -->
    <div class="modal fade" id="deleteCertificateConferenceModal" tabindex="-1" aria-labelledby="deleteCertificateConferenceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCertificateConferenceModalLabel">Delete Certificate/Conference</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="delete_title"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="admin.php">
                        <input type="hidden" name="certificate_conference_id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_certificate_conference" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme selector preview
            const themeSelect = document.getElementById('theme');
            if (themeSelect) {
                const previewLink = document.createElement('link');
                previewLink.id = 'theme-preview-css';
                previewLink.rel = 'stylesheet';
                document.head.appendChild(previewLink);
                
                const currentThemeSpan = document.getElementById('currentTheme');
                
                function updateThemePreview() {
                    const selectedTheme = themeSelect.value;
                    if (currentThemeSpan) {
                        currentThemeSpan.textContent = selectedTheme.charAt(0).toUpperCase() + selectedTheme.slice(1);
                    }
                    
                    if (selectedTheme === 'light') {
                        previewLink.href = '';
                    } else {
                        previewLink.href = 'css/theme-' + selectedTheme + '.css';
                    }
                }
                
                // Initial update
                updateThemePreview();
                
                themeSelect.addEventListener('change', updateThemePreview);
            }
            
            // Experience form handling
            const addResponsibilityButton = document.getElementById('add_responsibility_item');
            if (addResponsibilityButton) {
                addResponsibilityButton.addEventListener('click', function() {
                    const container = document.getElementById('responsibilities_container');
                    const index = container.children.length;
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="experience[description_items][]" placeholder="Responsibility item">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            }
            
            // Edit experience form handling
            const editAddResponsibilityButton = document.getElementById('edit_add_responsibility_item');
            if (editAddResponsibilityButton) {
                editAddResponsibilityButton.addEventListener('click', function() {
                    const container = document.getElementById('edit_responsibilities_container');
                    const index = container.children.length;
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="experience[description_items][]" placeholder="Responsibility item">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            }
            
            // Education form handling - this section isn't needed, removing it
            const addEducationItems = document.querySelectorAll('.add-education-item');
            if (addEducationItems.length > 0) {
                console.log('Found education items buttons:', addEducationItems);
                addEducationItems.forEach(button => {
                    button.addEventListener('click', function() {
                        const container = this.closest('form').querySelector('.education-items-container');
                        const index = container.children.length;
                        
                        const div = document.createElement('div');
                        div.className = 'input-group mb-2';
                        div.innerHTML = `
                            <input type="text" class="form-control" name="education[description_items][]" placeholder="Education detail">
                            <button type="button" class="btn btn-outline-danger remove-item">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        
                        container.appendChild(div);
                        
                        div.querySelector('.remove-item').addEventListener('click', function() {
                            div.remove();
                        });
                    });
                });
            } else {
                console.log('No .add-education-item elements found - this is expected');
            }
            
            // Populate modal data for experience edit
            const experienceModals = document.querySelectorAll('.edit-experience-btn');
            experienceModals.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const jobTitle = this.dataset.jobTitle;
                    const company = this.dataset.company;
                    const startDate = this.dataset.startDate;
                    const endDate = this.dataset.endDate;
                    const location = this.dataset.location;
                    let description = [];
                    
                    try {
                        description = JSON.parse(this.dataset.description);
                    } catch (e) {
                        console.error('Error parsing description:', e);
                    }
                    
                    document.getElementById('edit_exp_id').value = id;
                    document.getElementById('edit_exp_job_title').value = jobTitle;
                    document.getElementById('edit_exp_company').value = company;
                    document.getElementById('edit_exp_start_date').value = startDate;
                    document.getElementById('edit_exp_end_date').value = endDate;
                    document.getElementById('edit_exp_location').value = location;
                    
                    const container = document.getElementById('edit_responsibilities_container');
                    container.innerHTML = '';
                    
                    if (Array.isArray(description)) {
                        description.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'input-group mb-2';
                            div.innerHTML = `
                                <input type="text" class="form-control" name="experience[description_items][]" value="${item.replace(/"/g, '&quot;')}" placeholder="Responsibility item">
                                <button type="button" class="btn btn-outline-danger remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            
                            container.appendChild(div);
                            
                            div.querySelector('.remove-item').addEventListener('click', function() {
                                div.remove();
                            });
                        });
                    }
                });
            });
            
            // Populate modal data for education edit
            const educationModals = document.querySelectorAll('.edit-education-btn');
            educationModals.forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Education button clicked, dataset:', this.dataset);
                    
                    const id = this.dataset.id;
                    const degree = this.dataset.degree;
                    const institution = this.dataset.institution;
                    // Convert kebab-case data attributes to camelCase for dataset
                    // data-start-date becomes dataset.startDate
                    const startDate = this.dataset.startDate; 
                    const endDate = this.dataset.endDate;
                    const location = this.dataset.location;
                    let description = [];
                    
                    try {
                        description = JSON.parse(this.dataset.description);
                        console.log('Parsed description:', description);
                    } catch (e) {
                        console.error('Error parsing description:', e);
                    }
                    
                    console.log('Values:', {id, degree, institution, startDate, endDate, location});
                    
                    document.getElementById('edit_edu_id').value = id;
                    document.getElementById('edit_edu_degree').value = degree;
                    document.getElementById('edit_edu_institution').value = institution;
                    document.getElementById('edit_edu_start_date').value = startDate;
                    document.getElementById('edit_edu_end_date').value = endDate;
                    document.getElementById('edit_edu_location').value = location;
                    
                    const container = document.getElementById('edit_education_details_container');
                    console.log('Container element:', container);
                    container.innerHTML = '';
                    
                    if (Array.isArray(description)) {
                        description.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'input-group mb-2';
                            div.innerHTML = `
                                <input type="text" class="form-control" name="education[description_items][]" value="${item.replace(/"/g, '&quot;')}" placeholder="Education detail">
                                <button type="button" class="btn btn-outline-danger remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            
                            container.appendChild(div);
                        });
                    }
                    
                    // Add click event listeners to newly added remove buttons
                    container.querySelectorAll('.remove-item').forEach(btn => {
                        btn.addEventListener('click', function() {
                            this.closest('.input-group').remove();
                        });
                    });
                });
            });
            
            // Add event listener for adding education details
            const addEducationDetailBtn = document.getElementById('add_education_detail');
            if (addEducationDetailBtn) {
                console.log('Found add education detail button:', addEducationDetailBtn);
                addEducationDetailBtn.addEventListener('click', function() {
                    console.log('Add education detail button clicked');
                    const container = document.getElementById('education_details_container');
                    console.log('Container for education details:', container);
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="education[description_items][]" placeholder="Education detail">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            } else {
                console.error('Add education detail button not found!');
            }
            
            // Add event listener for adding education details in edit modal
            const editAddEducationDetailBtn = document.getElementById('edit_add_education_detail');
            if (editAddEducationDetailBtn) {
                console.log('Found edit add education detail button:', editAddEducationDetailBtn);
                editAddEducationDetailBtn.addEventListener('click', function() {
                    console.log('Edit add education detail button clicked');
                    const container = document.getElementById('edit_education_details_container');
                    console.log('Container for edit education details:', container);
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="education[description_items][]" placeholder="Education detail">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            } else {
                console.error('Edit add education detail button not found!');
            }
            
            // Delete confirmations
            document.querySelectorAll('[data-bs-target="#deleteExperienceModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_exp_id').value = this.dataset.id;
                    document.getElementById('delete_exp_title').textContent = this.dataset.title;
                });
            });
            
            document.querySelectorAll('[data-bs-target="#deleteEducationModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_edu_id').value = this.dataset.id;
                    document.getElementById('delete_edu_title').textContent = this.dataset.title;
                });
            });
            
            document.querySelectorAll('[data-bs-target="#deleteSkillModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_skill_id').value = this.dataset.id;
                    document.getElementById('delete_skill_title').textContent = this.dataset.title;
                });
            });
            
            document.querySelectorAll('[data-bs-target="#deleteAchievementModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_achievement_id').value = this.dataset.id;
                    document.getElementById('delete_achievement_title').textContent = this.dataset.title;
                });
            });
            
            document.querySelectorAll('[data-bs-target="#deleteProjectModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_project_id').value = this.dataset.id;
                    document.getElementById('delete_project_title').textContent = this.dataset.title;
                });
            });
            
            // Populate modal data for achievement edit
            const achievementModals = document.querySelectorAll('.edit-achievement-btn');
            achievementModals.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const title = this.dataset.title;
                    const description = this.dataset.description;
                    const date = this.dataset.date;
                    
                    document.getElementById('edit_achievement_id').value = id;
                    document.getElementById('edit_achievement_title').value = title;
                    document.getElementById('edit_achievement_description').value = description;
                    document.getElementById('edit_achievement_date').value = date;
                });
            });
            
            // Handle project technologies
            const addTechnologyButton = document.getElementById('add_technology_item');
            if (addTechnologyButton) {
                addTechnologyButton.addEventListener('click', function() {
                    const container = document.getElementById('technologies_container');
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="project[technologies][]" placeholder="Technology name">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            }
            
            // Edit project technologies
            const editAddTechnologyButton = document.getElementById('edit_add_technology_item');
            if (editAddTechnologyButton) {
                editAddTechnologyButton.addEventListener('click', function() {
                    const container = document.getElementById('edit_technologies_container');
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="project[technologies][]" placeholder="Technology name">
                        <button type="button" class="btn btn-outline-danger remove-item">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    container.appendChild(div);
                    
                    div.querySelector('.remove-item').addEventListener('click', function() {
                        div.remove();
                    });
                });
            }
            
            // Populate modal data for project edit
            const projectModals = document.querySelectorAll('.edit-project-btn');
            projectModals.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const title = this.dataset.title;
                    const description = this.dataset.description;
                    const link = this.dataset.link;
                    const image = this.dataset.image;
                    let technologies = [];
                    
                    try {
                        technologies = JSON.parse(this.dataset.technologies);
                    } catch (e) {
                        console.error('Error parsing technologies:', e);
                    }
                    
                    document.getElementById('edit_project_id').value = id;
                    document.getElementById('edit_project_title').value = title;
                    document.getElementById('edit_project_description').value = description;
                    document.getElementById('edit_project_link').value = link;
                    document.getElementById('edit_project_image').value = image;
                    
                    const container = document.getElementById('edit_technologies_container');
                    container.innerHTML = '';
                    
                    if (Array.isArray(technologies)) {
                        technologies.forEach(tech => {
                            const div = document.createElement('div');
                            div.className = 'input-group mb-2';
                            div.innerHTML = `
                                <input type="text" class="form-control" name="project[technologies][]" value="${tech.replace(/"/g, '&quot;')}" placeholder="Technology name">
                                <button type="button" class="btn btn-outline-danger remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            
                            container.appendChild(div);
                            
                            div.querySelector('.remove-item').addEventListener('click', function() {
                                div.remove();
                            });
                        });
                    }
                });
            });

            // Certificate/Conference modals
            document.querySelectorAll('.edit-certificate-conference-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('editCertificateConferenceModal');
                    modal.querySelector('#edit_id').value = this.dataset.id;
                    modal.querySelector('#edit_title').value = this.dataset.title;
                    modal.querySelector('#edit_description').value = this.dataset.description;
                    modal.querySelector('#edit_date').value = this.dataset.date;
                    modal.querySelector('#edit_type').value = this.dataset.type;
                    modal.querySelector('#edit_issuer').value = this.dataset.issuer;
                    modal.querySelector('#edit_url').value = this.dataset.url;
                });
            });

            document.querySelectorAll('[data-bs-target="#deleteCertificateConferenceModal"]').forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('deleteCertificateConferenceModal');
                    modal.querySelector('#delete_id').value = this.dataset.id;
                    modal.querySelector('#delete_title').textContent = this.dataset.title;
                });
            });
        });
    </script>
</body>
</html> 