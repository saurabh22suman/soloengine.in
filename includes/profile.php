<?php
// Fetch skills if not already fetched
if (!isset($skillsByCategory)) {
    // Include the database connection if needed
    if (!isset($pdo)) {
        require_once __DIR__ . '/db_connect.php';
        $pdo = getDbConnection();
    }
    
    // Fetch skills
    $stmt = $pdo->query('SELECT * FROM skills ORDER BY category, level DESC');
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group skills by category
    $skillsByCategory = [];
    foreach ($skills as $skill) {
        $skillsByCategory[$skill['category']][] = $skill;
    }
}
?>

<section id="profile" class="mb-5">
    <div class="card profile-card">
        <div class="card-header text-center">
            <h3><i class="fas fa-user-circle me-2"></i>About Me</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 position-relative">
                    <div class="float-md-end ms-md-4 mb-4 text-center text-md-start">
                        <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="<?php echo htmlspecialchars($profile['name']); ?> Profile Picture" class="img-fluid profile-img border mb-3">
                        <div class="social-links-container d-print-none">
                            <a href="https://linkedin.com/in/<?php echo htmlspecialchars(str_replace('linkedin.com/in/', '', $profile['linkedin'])); ?>" target="_blank" class="social-link mx-1"><i class="fab fa-linkedin-in"></i></a>
                            <a href="https://<?php echo htmlspecialchars($profile['github']); ?>" target="_blank" class="social-link mx-1"><i class="fab fa-github"></i></a>
                            <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="social-link mx-1"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                    <h2 class="card-title mb-1"><?php echo htmlspecialchars($profile['name']); ?></h2>
                    <h4 class="text-muted mb-3"><?php echo htmlspecialchars($profile['job_title']); ?></h4>
                    <div class="profile-summary mb-4">
                        <p class="lead"><?php echo htmlspecialchars($profile['summary']); ?></p>
                    </div>
                    <div class="contact-info-grid mb-4">
                        <div class="contact-item">
                            <i class="fas fa-envelope contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">Email</span>
                                <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" class="contact-value print-link"><?php echo htmlspecialchars($profile['email']); ?></a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">Phone</span>
                                <a href="tel:<?php echo htmlspecialchars(str_replace(' ', '', $profile['phone'])); ?>" class="contact-value print-link"><?php echo htmlspecialchars($profile['phone']); ?></a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">Location</span>
                                <span class="contact-value"><?php echo htmlspecialchars($profile['location']); ?></span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-linkedin contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">LinkedIn</span>
                                <a href="https://<?php echo htmlspecialchars($profile['linkedin']); ?>" target="_blank" class="contact-value print-link"><?php echo htmlspecialchars($profile['linkedin']); ?></a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-globe contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">Website</span>
                                <a href="https://<?php echo htmlspecialchars($profile['website']); ?>" target="_blank" class="contact-value print-link"><?php echo htmlspecialchars($profile['website']); ?></a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fab fa-github contact-icon"></i>
                            <div class="contact-details">
                                <span class="contact-label">GitHub</span>
                                <a href="https://<?php echo htmlspecialchars($profile['github']); ?>" target="_blank" class="contact-value print-link"><?php echo htmlspecialchars($profile['github']); ?></a>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach ($skillsByCategory as $category => $categorySkills): ?>
                    <div class="skill-category mb-2">
                        <h5 class="skill-heading"><?php echo htmlspecialchars($category); ?></h5>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($categorySkills as $skill): 
                                $bgClass = 'bg-primary';
                                switch($skill['level']) {
                                    case 5: $bgClass = 'bg-success'; break;
                                    case 4: $bgClass = 'bg-primary'; break;
                                    case 3: $bgClass = 'bg-info'; break;
                                    case 2: $bgClass = 'bg-warning'; break;
                                    case 1: $bgClass = 'bg-secondary'; break;
                                }
                            ?>
                            <span class="badge <?php echo $bgClass; ?> me-2 mb-2"><?php echo htmlspecialchars($skill['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4 d-flex justify-content-end d-print-none">
                        <button class="btn btn-outline-primary download-resume" id="download-resume-btn" aria-label="Download Resume PDF">
                            <i class="fas fa-download me-2"></i>Download Resume
                        </button>
                        <button class="btn btn-print ms-2" id="print-resume-btn" aria-label="Print Resume">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> 
</section> 