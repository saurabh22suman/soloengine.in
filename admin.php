<?php
// Admin page for managing the database content
session_start();

// Include the database connection
require_once 'includes/db_connect.php';

// Authentication logic using database
$is_logged_in = false;
$login_error = '';
$password_message = '';

if (isset($_POST['login'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM admin_settings WHERE username = ?');
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $_POST['password'] == $user['password']) { // In production, use password_verify()
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['username'];
        $is_logged_in = true;
    } else {
        $login_error = "Invalid credentials";
    }
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
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
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $stmt = $pdo->prepare('SELECT * FROM admin_settings WHERE id = 1');
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($_POST['current_password'] == $admin['password']) {
            if ($_POST['new_password'] == $_POST['confirm_password']) {
                if (strlen($_POST['new_password']) >= 6) {
                    $stmt = $pdo->prepare('UPDATE admin_settings SET password = ? WHERE id = 1');
                    $stmt->execute([$_POST['new_password']]);
                    $password_message = "Password updated successfully! Please log in again.";
                    
                    // Force re-login for security
                    session_destroy();
                    header('Location: admin.php?password_changed=1');
                    exit;
                } else {
                    $password_message = "New password must be at least 6 characters long.";
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
                                    
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Change Password</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="admin.php">
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                    <div class="form-text">Password must be at least 6 characters long.</div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Common function for adding dynamic input groups
        function setupDynamicInputs(addButtonId, containerId, inputName, placeholder) {
            document.getElementById(addButtonId).addEventListener('click', function() {
                const container = document.getElementById(containerId);
                const newItem = document.createElement('div');
                newItem.className = 'input-group mb-2';
                newItem.innerHTML = `
                    <input type="text" class="form-control" name="${inputName}" placeholder="${placeholder}">
                    <button class="btn btn-outline-danger remove-item" type="button"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(newItem);
                
                // Add event listener to the new remove button
                newItem.querySelector('.remove-item').addEventListener('click', function() {
                    container.removeChild(newItem);
                });
            });
        }
        
        // Add event listeners to all existing remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.input-group').remove();
            });
        });
        
        // Setup dynamic inputs for various forms
        setupDynamicInputs('add_responsibility_item', 'responsibilities_container', 'experience[description_items][]', 'Responsibility item');
        setupDynamicInputs('edit_add_responsibility_item', 'edit_responsibilities_container', 'experience[description_items][]', 'Responsibility item');
        setupDynamicInputs('add_education_detail', 'education_details_container', 'education[description_items][]', 'Education detail');
        setupDynamicInputs('edit_add_education_detail', 'edit_education_details_container', 'education[description_items][]', 'Education detail');
        setupDynamicInputs('add_technology_item', 'technologies_container', 'project[technologies][]', 'Technology');
        setupDynamicInputs('edit_add_technology_item', 'edit_technologies_container', 'project[technologies][]', 'Technology');

        // Experience edit modal
        document.querySelectorAll('.edit-experience-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const jobTitle = this.getAttribute('data-job-title');
                const company = this.getAttribute('data-company');
                const startDate = this.getAttribute('data-start-date');
                const endDate = this.getAttribute('data-end-date');
                const location = this.getAttribute('data-location');
                const description = JSON.parse(this.getAttribute('data-description') || '[]');
                
                document.getElementById('edit_exp_id').value = id;
                document.getElementById('edit_exp_job_title').value = jobTitle;
                document.getElementById('edit_exp_company').value = company;
                document.getElementById('edit_exp_start_date').value = startDate;
                document.getElementById('edit_exp_end_date').value = endDate;
                document.getElementById('edit_exp_location').value = location;
                
                // Clear existing items
                const container = document.getElementById('edit_responsibilities_container');
                container.innerHTML = '';
                
                // Add items from description
                if (Array.isArray(description)) {
                    description.forEach(item => {
                        const newItem = document.createElement('div');
                        newItem.className = 'input-group mb-2';
                        newItem.innerHTML = `
                            <input type="text" class="form-control" name="experience[description_items][]" value="${item.replace(/"/g, '&quot;')}" placeholder="Responsibility item">
                            <button class="btn btn-outline-danger remove-item" type="button"><i class="fas fa-times"></i></button>
                        `;
                        container.appendChild(newItem);
                        
                        // Add event listener to the remove button
                        newItem.querySelector('.remove-item').addEventListener('click', function() {
                            container.removeChild(newItem);
                        });
                    });
                }
            });
        });
        
        // Education edit modal
        document.querySelectorAll('.edit-education-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const degree = this.getAttribute('data-degree');
                const institution = this.getAttribute('data-institution');
                const startDate = this.getAttribute('data-start-date');
                const endDate = this.getAttribute('data-end-date');
                const location = this.getAttribute('data-location');
                const description = JSON.parse(this.getAttribute('data-description') || '[]');
                
                document.getElementById('edit_edu_id').value = id;
                document.getElementById('edit_edu_degree').value = degree;
                document.getElementById('edit_edu_institution').value = institution;
                document.getElementById('edit_edu_start_date').value = startDate;
                document.getElementById('edit_edu_end_date').value = endDate;
                document.getElementById('edit_edu_location').value = location;
                
                // Clear existing items
                const container = document.getElementById('edit_education_details_container');
                container.innerHTML = '';
                
                // Add items from description
                if (Array.isArray(description)) {
                    description.forEach(item => {
                        const newItem = document.createElement('div');
                        newItem.className = 'input-group mb-2';
                        newItem.innerHTML = `
                            <input type="text" class="form-control" name="education[description_items][]" value="${item.replace(/"/g, '&quot;')}" placeholder="Education detail">
                            <button class="btn btn-outline-danger remove-item" type="button"><i class="fas fa-times"></i></button>
                        `;
                        container.appendChild(newItem);
                        
                        // Add event listener to the remove button
                        newItem.querySelector('.remove-item').addEventListener('click', function() {
                            container.removeChild(newItem);
                        });
                    });
                }
            });
        });
        
        // Skills edit modal
        document.querySelectorAll('.edit-skill-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const category = this.getAttribute('data-category');
                const name = this.getAttribute('data-name');
                const level = this.getAttribute('data-level');
                
                document.getElementById('edit_skill_id').value = id;
                document.getElementById('edit_skill_category').value = category;
                document.getElementById('edit_skill_name').value = name;
                document.getElementById('edit_skill_level').value = level;
            });
        });
        
        // Achievement edit modal
        document.querySelectorAll('.edit-achievement-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const description = this.getAttribute('data-description');
                const date = this.getAttribute('data-date');
                
                document.getElementById('edit_achievement_id').value = id;
                document.getElementById('edit_achievement_title').value = title;
                document.getElementById('edit_achievement_description').value = description;
                document.getElementById('edit_achievement_date').value = date;
            });
        });
        
        // Project edit modal
        document.querySelectorAll('.edit-project-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const title = this.getAttribute('data-title');
                const description = this.getAttribute('data-description');
                const link = this.getAttribute('data-link');
                const image = this.getAttribute('data-image');
                const technologies = JSON.parse(this.getAttribute('data-technologies') || '[]');
                
                document.getElementById('edit_project_id').value = id;
                document.getElementById('edit_project_title').value = title;
                document.getElementById('edit_project_description').value = description;
                document.getElementById('edit_project_link').value = link;
                document.getElementById('edit_project_image').value = image;
                
                // Clear existing items
                const container = document.getElementById('edit_technologies_container');
                container.innerHTML = '';
                
                // Add items from technologies
                if (Array.isArray(technologies)) {
                    technologies.forEach(tech => {
                        const newItem = document.createElement('div');
                        newItem.className = 'input-group mb-2';
                        newItem.innerHTML = `
                            <input type="text" class="form-control" name="project[technologies][]" value="${tech.replace(/"/g, '&quot;')}" placeholder="Technology">
                            <button class="btn btn-outline-danger remove-item" type="button"><i class="fas fa-times"></i></button>
                        `;
                        container.appendChild(newItem);
                        
                        // Add event listener to the remove button
                        newItem.querySelector('.remove-item').addEventListener('click', function() {
                            container.removeChild(newItem);
                        });
                    });
                }
            });
        });
        
        // Setup delete modals
        function setupDeleteModal(modalId, idField, titleField) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const title = button.getAttribute('data-title');
                    
                    document.getElementById(idField).value = id;
                    if (titleField) {
                        document.getElementById(titleField).textContent = title;
                    }
                });
            }
        }
        
        setupDeleteModal('deleteExperienceModal', 'delete_exp_id', 'delete_exp_title');
        setupDeleteModal('deleteEducationModal', 'delete_edu_id', 'delete_edu_title');
        setupDeleteModal('deleteSkillModal', 'delete_skill_id', 'delete_skill_title');
        setupDeleteModal('deleteAchievementModal', 'delete_achievement_id', 'delete_achievement_title');
        setupDeleteModal('deleteProjectModal', 'delete_project_id', 'delete_project_title');
    });
    </script>
</body>
</html> 