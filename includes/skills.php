<?php
// Include the database connection if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_connect.php';
}
$pdo = getDbConnection();

// Fetch skills by category
$stmt = $pdo->query('SELECT DISTINCT category FROM skills ORDER BY category');
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Function to get badge class based on skill level
function getSkillBadgeClass($level) {
    switch($level) {
        case 5: return 'bg-success';
        case 4: return 'bg-primary';
        case 3: return 'bg-info';
        case 2: return 'bg-warning';
        case 1: return 'bg-secondary';
        default: return 'bg-dark';
    }
}
?>

<section id="skills" class="mb-5">
    <div class="card">
        <div class="card-header bg-light">
            <h3><i class="fas fa-tools me-2"></i>Skills</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($categories as $category): 
                    // Get skills for this category
                    $stmt = $pdo->prepare('SELECT * FROM skills WHERE category = ? ORDER BY level DESC, name');
                    $stmt->execute([$category]);
                    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="col-md-6 mb-4">
                    <div class="skill-category">
                        <h4><?php echo htmlspecialchars($category); ?></h4>
                        <div class="skill-tags">
                            <?php foreach ($skills as $skill): ?>
                            <span class="badge <?php echo getSkillBadgeClass($skill['level']); ?> me-2 mb-2">
                                <?php echo htmlspecialchars($skill['name']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section> 