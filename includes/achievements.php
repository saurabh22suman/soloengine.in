<?php
// Include the database connection if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_connect.php';
}
$pdo = getDbConnection();

// Fetch achievements data
$stmt = $pdo->query('SELECT * FROM achievements ORDER BY date DESC');
$allAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate achievements and conferences
$achievements = [];
$conferences = [];

foreach ($allAchievements as $item) {
    if (strpos(strtolower($item['title']), 'conference') !== false || 
        strpos(strtolower($item['title']), 'pycon') !== false) {
        $conferences[] = $item;
    } else {
        $achievements[] = $item;
    }
}
?>

<section id="achievements" class="mb-5">
    <div class="card">
        <div class="card-header bg-light">
            <h3><i class="fas fa-trophy me-2"></i>Achievements & Conferences</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-3">Achievements</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($achievements as $achievement): ?>
                        <li class="mb-3">
                            <h5><?php echo htmlspecialchars($achievement['title']); ?></h5>
                            <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-3">Conferences & Courses</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($conferences as $conference): ?>
                        <li class="mb-3">
                            <h5><?php echo htmlspecialchars($conference['title']); ?></h5>
                            <p><?php echo htmlspecialchars($conference['description']); ?></p>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($conferences)): ?>
                        <li class="mb-3">
                            <p class="text-muted">No conferences or courses listed.</p>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section> 