<?php
// Include the database connection if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_connect.php';
}
$pdo = getDbConnection();

// Fetch education data
$stmt = $pdo->query('SELECT * FROM education ORDER BY end_date DESC');
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section id="education" class="mb-5">
    <div class="card">
        <div class="card-header bg-light">
            <h3><i class="fas fa-graduation-cap me-2"></i>Education</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($educations as $index => $education): 
                    // Decode the JSON description
                    $descriptionItems = json_decode($education['description'], true);
                ?>
                <div class="timeline-item <?php echo ($index < count($educations) - 1) ? 'mb-4' : ''; ?>">
                    <h4><?php echo htmlspecialchars($education['degree']); ?></h4>
                    <p class="text-muted">
                        <span><i class="fas fa-university me-2"></i><?php echo htmlspecialchars($education['institution']); ?></span>
                        <span class="mx-2">|</span>
                        <span><i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($education['start_date']); ?> - <?php echo htmlspecialchars($education['end_date']); ?></span>
                        <span class="mx-2">|</span>
                        <span><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($education['location']); ?></span>
                    </p>
                    <?php if (!empty($descriptionItems)): ?>
                    <ul>
                        <?php foreach ($descriptionItems as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section> 