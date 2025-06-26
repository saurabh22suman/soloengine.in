<?php
/**
 * RAG Database Populator
 * 
 * Imports portfolio data from existing database tables into the RAG system
 * 
 * SECURITY: Protected by admin authentication check
 */

// Start session for security verification
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to access this script
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Include necessary files
require_once 'includes/db_connect.php';
require_once 'includes/vector_store.php';

$pdo = getDbConnection();
$vectorStore = new VectorStore();

// Status messages
$messages = [];
$errors = [];

// Process form submission
$import_selected = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "CSRF validation failed";
    } else {
        // Check what data should be imported
        $import_selected = true;
        $imported_count = 0;
        
        // Import Profile
        if (isset($_POST['import_profile']) && $_POST['import_profile'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM profile LIMIT 1');
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($profile) {
                    $title = "Personal Profile";
                    $source = "Portfolio Database";
                    $content = "Name: {$profile['name']}\n";
                    $content .= "Job Title: {$profile['job_title']}\n";
                    $content .= "Summary: {$profile['summary']}\n";
                    $content .= "Email: {$profile['email']}\n";
                    $content .= "Phone: {$profile['phone']}\n";
                    $content .= "Location: {$profile['location']}\n";
                    
                    if (!empty($profile['linkedin'])) {
                        $content .= "LinkedIn: {$profile['linkedin']}\n";
                    }
                    
                    if (!empty($profile['website'])) {
                        $content .= "Website: {$profile['website']}\n";
                    }
                    
                    if (!empty($profile['github'])) {
                        $content .= "GitHub: {$profile['github']}\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Profile imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import profile";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing profile: " . $e->getMessage();
            }
        }
        
        // Import Experience
        if (isset($_POST['import_experience']) && $_POST['import_experience'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM experience ORDER BY start_date DESC');
                $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($experiences) {
                    $title = "Work Experience";
                    $source = "Portfolio Database";
                    $content = "# Work Experience\n\n";
                    
                    foreach ($experiences as $exp) {
                        $content .= "## {$exp['job_title']} at {$exp['company']}\n";
                        $content .= "{$exp['start_date']} - {$exp['end_date']}\n";
                        $content .= "Location: {$exp['location']}\n\n";
                        
                        // Handle both JSON and plain text descriptions
                        $description = $exp['description'];
                        if (json_decode($description) !== null) {
                            $items = json_decode($description, true);
                            foreach ($items as $item) {
                                $content .= "- " . $item . "\n";
                            }
                        } else {
                            $content .= $description . "\n";
                        }
                        $content .= "\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Work experience imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import work experience";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing experience: " . $e->getMessage();
            }
        }
        
        // Import Education
        if (isset($_POST['import_education']) && $_POST['import_education'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM education ORDER BY start_date DESC');
                $education = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($education) {
                    $title = "Education History";
                    $source = "Portfolio Database";
                    $content = "# Education History\n\n";
                    
                    foreach ($education as $edu) {
                        $content .= "## {$edu['degree']} at {$edu['institution']}\n";
                        $content .= "{$edu['start_date']} - {$edu['end_date']}\n";
                        $content .= "Location: {$edu['location']}\n\n";
                        
                        // Handle both JSON and plain text descriptions
                        $description = $edu['description'];
                        if (json_decode($description) !== null) {
                            $items = json_decode($description, true);
                            foreach ($items as $item) {
                                $content .= "- " . $item . "\n";
                            }
                        } else {
                            $content .= $description . "\n";
                        }
                        $content .= "\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Education history imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import education history";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing education: " . $e->getMessage();
            }
        }
        
        // Import Skills
        if (isset($_POST['import_skills']) && $_POST['import_skills'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM skills ORDER BY category, level DESC');
                $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($skills) {
                    // Group skills by category
                    $skillsByCategory = [];
                    foreach ($skills as $skill) {
                        $category = $skill['category'];
                        if (!isset($skillsByCategory[$category])) {
                            $skillsByCategory[$category] = [];
                        }
                        $skillsByCategory[$category][] = $skill;
                    }
                    
                    $title = "Skills and Expertise";
                    $source = "Portfolio Database";
                    $content = "# Skills and Expertise\n\n";
                    
                    foreach ($skillsByCategory as $category => $categorySkills) {
                        $content .= "## " . $category . "\n\n";
                        foreach ($categorySkills as $skill) {
                            $level = '';
                            switch ($skill['level']) {
                                case 5: $level = 'Expert'; break;
                                case 4: $level = 'Advanced'; break;
                                case 3: $level = 'Intermediate'; break;
                                case 2: $level = 'Basic'; break;
                                case 1: $level = 'Beginner'; break;
                                default: $level = 'Intermediate';
                            }
                            $content .= "- {$skill['name']} ({$level})\n";
                        }
                        $content .= "\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Skills imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import skills";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing skills: " . $e->getMessage();
            }
        }
        
        // Import Projects
        if (isset($_POST['import_projects']) && $_POST['import_projects'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM projects');
                $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($projects) {
                    $title = "Portfolio Projects";
                    $source = "Portfolio Database";
                    $content = "# Portfolio Projects\n\n";
                    
                    foreach ($projects as $project) {
                        $content .= "## {$project['title']}\n\n";
                        $content .= "{$project['description']}\n\n";
                        
                        if (!empty($project['technologies'])) {
                            $content .= "Technologies: {$project['technologies']}\n\n";
                        }
                        
                        if (!empty($project['link'])) {
                            $content .= "Link: {$project['link']}\n\n";
                        }
                        
                        $content .= "---\n\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Projects imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import projects";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing projects: " . $e->getMessage();
            }
        }
        
        // Import Achievements
        if (isset($_POST['import_achievements']) && $_POST['import_achievements'] === 'yes') {
            try {
                $stmt = $pdo->query('SELECT * FROM achievements ORDER BY date DESC');
                $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($achievements) {
                    $title = "Achievements";
                    $source = "Portfolio Database";
                    $content = "# Achievements\n\n";
                    
                    foreach ($achievements as $achievement) {
                        $content .= "## {$achievement['title']}\n";
                        $content .= "Date: {$achievement['date']}\n\n";
                        $content .= "{$achievement['description']}\n\n";
                    }
                    
                    $documentId = $vectorStore->addDocument($title, $source, $content);
                    
                    if ($documentId) {
                        $messages[] = "Achievements imported successfully (ID: {$documentId})";
                        $imported_count++;
                    } else {
                        $errors[] = "Failed to import achievements";
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error importing achievements: " . $e->getMessage();
            }
        }
        
        // Summary message
        if ($imported_count > 0) {
            $messages[] = "Completed importing {$imported_count} documents to RAG system.";
        } else {
            $errors[] = "No documents were imported.";
        }
    }
}

// Prepare document count
try {
    $documentCount = count($vectorStore->listDocuments());
} catch (Exception $e) {
    $documentCount = 0;
}

// Create page header
$pageTitle = "Import Portfolio Data to RAG";
require_once 'header.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h2>Import Portfolio Data to RAG</h2>
        </div>
        <div class="card-body">
            <p class="mb-4">
                This tool allows you to import your existing portfolio data into the RAG system.
                The data will be converted to documents that the chat assistant can reference when answering questions.
            </p>
            
            <?php if (!empty($messages)): ?>
                <?php foreach($messages as $msg): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <?php foreach($errors as $error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="mb-4">
                <h3>Current RAG Status</h3>
                <div class="bg-light p-3 rounded">
                    <p><strong>Documents in RAG system:</strong> <?= $documentCount ?></p>
                </div>
            </div>
            
            <?php if ($import_selected): ?>
                <div class="mb-4">
                    <a href="rag_admin.php" class="btn btn-primary">View RAG Documents</a>
                    <a href="import_portfolio_data.php" class="btn btn-secondary">Import More Data</a>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <h3>Select Data to Import</h3>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="import_profile" name="import_profile" value="yes" checked>
                        <label class="form-check-label" for="import_profile">
                            Personal Profile
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="import_experience" name="import_experience" value="yes" checked>
                        <label class="form-check-label" for="import_experience">
                            Work Experience
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="import_education" name="import_education" value="yes" checked>
                        <label class="form-check-label" for="import_education">
                            Education
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="import_skills" name="import_skills" value="yes" checked>
                        <label class="form-check-label" for="import_skills">
                            Skills
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="import_projects" name="import_projects" value="yes" checked>
                        <label class="form-check-label" for="import_projects">
                            Projects
                        </label>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="import_achievements" name="import_achievements" value="yes" checked>
                        <label class="form-check-label" for="import_achievements">
                            Achievements
                        </label>
                    </div>
                    
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i> 
                        This will create new documents in the RAG system. Any existing documents with the same titles will remain (creating duplicates).
                        Consider clearing existing RAG documents first if you want to avoid duplicates.
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Import Selected Data</button>
                        <a href="admin.php" class="btn btn-secondary ms-2">Back to Admin</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
