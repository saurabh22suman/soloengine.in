<?php
// Include the database connection
require_once 'includes/db_connect.php';
$pdo = getDbConnection();

// Fetch profile data
$stmt = $pdo->prepare('SELECT * FROM profile WHERE id = 1');
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Include the header file
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <?php include 'includes/profile.php'; ?>
            <?php include 'includes/experience.php'; ?>
            <?php include 'includes/education.php'; ?>
            <?php include 'includes/skills.php'; ?>
            <?php include 'includes/achievements.php'; ?>
            <?php include 'includes/projects.php'; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 