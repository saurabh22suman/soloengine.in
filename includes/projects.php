<?php
// Include the database connection if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_connect.php';
}
$pdo = getDbConnection();

// Fetch projects data
$stmt = $pdo->query('SELECT * FROM projects');
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get icon class based on project title
function getProjectIcon($title) {
    if (strpos(strtolower($title), 'lock') !== false) {
        return 'fa-code-branch text-primary';
    } elseif (strpos(strtolower($title), 'encr') !== false) {
        return 'fa-shield-alt text-success';
    } elseif (strpos(strtolower($title), 'progress') !== false) {
        return 'fa-tasks text-info';
    } elseif (strpos(strtolower($title), 'short') !== false) {
        return 'fa-bolt text-warning';
    } elseif (strpos(strtolower($title), 'point of sale') !== false) {
        return 'fa-shopping-cart text-danger';
    } elseif (strpos(strtolower($title), 'portfolio') !== false) {
        return 'fa-file-code text-info';
    } else {
        return 'fa-code text-primary';
    }
}
?>

<section id="projects" class="mb-5">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Projects</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($projects as $index => $project): 
                        // Decode the technologies JSON
                        $technologies = json_decode($project['technologies'], true);
                        $iconClass = getProjectIcon($project['title']);
                    ?>
                    <!-- Project <?php echo $index + 1; ?> -->
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card project-card h-100">
                            <div class="card-body text-center">
                                <div class="project-icon mb-4">
                                    <i class="fas <?php echo $iconClass; ?> fa-3x"></i>
                                </div>
                                <h5 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                                <div class="d-flex justify-content-between mt-auto">
                                    <?php if (!empty($project['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['link']); ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fab fa-github me-1"></i> GitHub</a>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($technologies)): ?>
                                <div class="project-tags mt-3">
                                    <?php foreach ($technologies as $tech): 
                                        $badgeClass = 'bg-primary';
                                        if ($tech == 'Shell' || $tech == 'OpenSSL') {
                                            $badgeClass = 'bg-warning';
                                        } elseif ($tech == 'POSIX' || $tech == 'CLI' || $tech == 'MySQL') {
                                            $badgeClass = 'bg-info';
                                        } elseif ($tech == 'NFS' || $tech == 'Bash' || $tech == 'Utility') {
                                            $badgeClass = 'bg-secondary';
                                        } elseif ($tech == 'Automation' || $tech == 'Linux') {
                                            $badgeClass = 'bg-success';
                                        } elseif ($tech == 'PHP') {
                                            $badgeClass = 'bg-secondary';
                                        } elseif ($tech == 'Bootstrap') {
                                            $badgeClass = 'bg-info';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> me-1"><?php echo htmlspecialchars($tech); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section> 