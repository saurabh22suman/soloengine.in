<?php
// Include the database connection if not already included
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/db_connect.php';
}
$pdo = getDbConnection();

// Fetch achievements data
$stmt = $pdo->query('SELECT * FROM achievements ORDER BY date DESC');
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch certificates and conferences
$stmt = $pdo->query('SELECT * FROM certificates_conferences ORDER BY date DESC');
$certificates_conferences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate certificates and conferences
$certificates = array_filter($certificates_conferences, function($item) {
    return $item['type'] === 'certificate';
});

$conferences = array_filter($certificates_conferences, function($item) {
    return $item['type'] === 'conference';
});
?>

<section id="achievements" class="mb-5">
    <div class="card">
        <div class="card-header bg-light">
            <h3><i class="fas fa-trophy me-2"></i>Achievements & Certificates</h3>
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
                        <?php if (empty($achievements)): ?>
                        <li class="mb-3">
                            <p class="text-muted">No achievements listed.</p>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4 class="mb-3">Certificates & Conferences</h4>
                    <ul class="list-unstyled">
                        <?php foreach ($certificates_conferences as $item): ?>
                        <li class="mb-3">
                            <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($item['issuer']); ?> | 
                                <?php echo htmlspecialchars($item['date']); ?>
                                <?php if (!empty($item['url'])): ?>
                                | <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" rel="noopener noreferrer">View</a>
                                <?php endif; ?>
                            </small>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($certificates_conferences)): ?>
                        <li class="mb-3">
                            <p class="text-muted">No certificates or conferences listed.</p>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section> 