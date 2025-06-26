<?php
/**
 * Embedding Service Test
 * 
 * Tests the functionality of the embedding service with Hugging Face API
 * 
 * SECURITY: Protected by admin authentication check
 */

// Start session for security verification
session_start();

// CRITICAL SECURITY CHECK: Only allow admin users to access this test
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Include necessary files
require_once 'includes/db_connect.php';
require_once 'includes/env_loader.php';
require_once 'includes/embedding_service.php';

// Create page header
$pageTitle = "Embedding Service Test";
require_once 'header.php';

// Process test form
$testResults = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
    try {
        // Initialize embedding service
        $embeddingService = new EmbeddingService();
        
        // Generate embedding
        $startTime = microtime(true);
        $embedding = $embeddingService->getEmbedding($_POST['text']);
        $endTime = microtime(true);
        
        if ($embedding !== null) {
            $testResults = [
                'success' => true,
                'embedding' => $embedding,
                'dimension' => count($embedding),
                'time' => round(($endTime - $startTime) * 1000, 2), // ms
                'magnitude' => sqrt(array_sum(array_map(function($x) { return $x * $x; }, $embedding)))
            ];
            
            // Test similarity if second text is provided
            if (!empty($_POST['text2'])) {
                $embedding2 = $embeddingService->getEmbedding($_POST['text2']);
                if ($embedding2 !== null) {
                    $similarity = $embeddingService->cosineSimilarity($embedding, $embedding2);
                    $testResults['similarity'] = $similarity;
                    $testResults['text2'] = $_POST['text2'];
                }
            }
        } else {
            throw new Exception("Failed to generate embedding");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header">
            <h2>Embedding Service Test</h2>
        </div>
        <div class="card-body">
            <p>
                This tool tests the embedding service by generating vector embeddings for text input.
                It can also calculate the similarity between two text samples.
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="text" class="form-label">Text to Embed</label>
                    <textarea class="form-control" id="text" name="text" rows="3" required><?= isset($_POST['text']) ? htmlspecialchars($_POST['text']) : '' ?></textarea>
                    <div class="invalid-feedback">Please provide some text to embed</div>
                </div>
                
                <div class="mb-3">
                    <label for="text2" class="form-label">Compare with (Optional)</label>
                    <textarea class="form-control" id="text2" name="text2" rows="3"><?= isset($_POST['text2']) ? htmlspecialchars($_POST['text2']) : '' ?></textarea>
                    <div class="form-text">
                        If provided, the system will calculate similarity between the two texts
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Generate Embedding</button>
                </div>
            </form>
            
            <?php if ($testResults): ?>
                <hr>
                
                <h3>Test Results</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Stats</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Status
                                        <span class="badge bg-success">Success</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Dimensions
                                        <span class="badge bg-primary"><?= $testResults['dimension'] ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Processing Time
                                        <span class="badge bg-info"><?= $testResults['time'] ?> ms</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Vector Magnitude
                                        <span class="badge bg-secondary"><?= number_format($testResults['magnitude'], 4) ?></span>
                                    </li>
                                    <?php if (isset($testResults['similarity'])): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Similarity Score
                                            <span class="badge bg-primary"><?= number_format($testResults['similarity'], 4) ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title">Embedding Visualization</h5>
                            </div>
                            <div class="card-body">
                                <div id="embeddingChart" style="height: 200px;"></div>
                            </div>
                        </div>
                        
                        <?php if (isset($testResults['similarity'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Similarity Visualization</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-2">
                                        <div class="progress" style="height: 24px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $testResults['similarity'] * 100 ?>%;" 
                                                 aria-valuenow="<?= $testResults['similarity'] * 100 ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= number_format($testResults['similarity'] * 100, 1) ?>%
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-center">
                                        <?php
                                        $simScore = $testResults['similarity'];
                                        if ($simScore > 0.9) {
                                            echo "The texts are extremely similar";
                                        } elseif ($simScore > 0.8) {
                                            echo "The texts are very similar";
                                        } elseif ($simScore > 0.6) {
                                            echo "The texts are moderately similar";
                                        } elseif ($simScore > 0.4) {
                                            echo "The texts have some similarity";
                                        } else {
                                            echo "The texts are not very similar";
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="accordion mt-3" id="embeddingAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                View Raw Embedding Data
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#embeddingAccordion">
                            <div class="accordion-body">
                                <div class="small" style="max-height: 200px; overflow-y: auto;">
                                    <?php
                                    // Print first 20 dimensions and then abbreviated
                                    echo '[';
                                    $dimensions = $testResults['dimension'];
                                    $showCount = min(20, $dimensions);
                                    
                                    for ($i = 0; $i < $showCount; $i++) {
                                        echo number_format($testResults['embedding'][$i], 6);
                                        if ($i < $showCount - 1) {
                                            echo ', ';
                                        }
                                    }
                                    
                                    if ($dimensions > $showCount) {
                                        echo ', ... (' . ($dimensions - $showCount) . ' more)';
                                    }
                                    
                                    echo ']';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="d-flex justify-content-between mb-4">
        <a href="rag_admin.php" class="btn btn-secondary">Back to RAG Admin</a>
        <a href="test_env.php" class="btn btn-info">Check Configuration</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($testResults): ?>
    // Create chart for embedding visualization
    const ctx = document.getElementById('embeddingChart').getContext('2d');
    
    // Sample a subset of the embedding for visualization
    const embedding = <?= json_encode(array_slice($testResults['embedding'], 0, 30)) ?>;
    const labels = Array.from({length: embedding.length}, (_, i) => `Dim ${i+1}`);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Embedding Values',
                data: embedding,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Value: ${context.raw.toFixed(6)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
