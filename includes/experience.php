<?php
// Include the database connection if not already included
if (!isset($pdo)) {
    require_once __DIR__ . '/db_connect.php';
    $pdo = getDbConnection();
}

// Fetch experience data
// Ensure experience items are ordered by start date in descending order (newest first)
// SQLite doesn't handle date strings well, so we'll get all records and sort in PHP
$stmt = $pdo->query('SELECT * FROM experience');
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort experiences by start date (newest first)
usort($experiences, function($a, $b) {
    // Convert MM/YYYY format to timestamps for comparison
    $dateA = strtotime(str_replace('/', '-01-', $a['start_date']) . '-01');
    $dateB = strtotime(str_replace('/', '-01-', $b['start_date']) . '-01');
    
    // Sort in descending order (newest first)
    return $dateB - $dateA;
});
?>

<section id="experience" class="mb-5">
    <div class="card">
        <div class="card-header bg-light">
            <h3><i class="fas fa-briefcase me-2"></i>Work Experience</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($experiences as $index => $experience): 
                    // Decode the JSON description
                    $descriptionItems = json_decode($experience['description'], true);
                ?>
                <!-- Experience <?php echo $index + 1; ?> -->
                <div class="timeline-item <?php echo ($index < count($experiences) - 1) ? 'mb-4' : ''; ?>">
                    <h4><?php echo htmlspecialchars($experience['job_title']); ?></h4>
                    <p class="text-muted">
                        <span><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($experience['company']); ?></span>
                        <span class="mx-2">|</span>
                        <span><i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($experience['start_date']); ?> - <?php echo htmlspecialchars($experience['end_date']); ?></span>
                        <span class="mx-2">|</span>
                        <span><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($experience['location']); ?></span>
                    </p>
                    <ul>
                        <?php foreach ($descriptionItems as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section> 