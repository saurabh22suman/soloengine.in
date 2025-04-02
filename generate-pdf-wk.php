<?php
/**
 * PDF Generation using wkhtmltopdf
 * 
 * Generates a PDF of the resume with print.css styles applied
 */

// Check for wkhtmltopdf installation
if (!file_exists('/usr/bin/wkhtmltopdf') && !file_exists('/usr/local/bin/wkhtmltopdf')) {
    die("Please install wkhtmltopdf first. See wkhtmltopdf-setup.md for instructions.");
}

// Find the wkhtmltopdf binary
$wkhtmltopdf = file_exists('/usr/bin/wkhtmltopdf') ? '/usr/bin/wkhtmltopdf' : '/usr/local/bin/wkhtmltopdf';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Create temporary files
    $tempHtml = __DIR__ . '/temp_resume.html';
    $tempPdf = __DIR__ . '/temp_resume.pdf';
    
    // Start output buffer to capture PHP output
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Prakersh Maheshwari - Resume</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/print.css">
        <style>
            /* Force print styles to apply */
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background-color: white;
            }
            
            /* Hide elements we don't want in PDF */
            .d-print-none, header, footer, .btn, 
            .navbar-toggler, .social-icon, .social-links-container, 
            .print-message, #print-resume-btn {
                display: none !important;
            }
        </style>
    </head>
    <body class="is-printing preparing-for-print">
        <div class="container">
            <div class="row">
                <div class="col-md-10 offset-md-1">
                    <?php
                    // Include core sections
                    include 'includes/profile.php';
                    include 'includes/experience.php';
                    include 'includes/education.php';
                    include 'includes/skills.php';
                    include 'includes/achievements.php';
                    include 'includes/projects.php';
                    ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    // Get the HTML content and end buffering
    $htmlContent = ob_get_clean();
    
    // Write the HTML to a temp file
    file_put_contents($tempHtml, $htmlContent);
    
    // Build the wkhtmltopdf command with options
    $options = [
        '--page-size A4',
        '--margin-top 10mm',
        '--margin-right 10mm',
        '--margin-bottom 10mm',
        '--margin-left 10mm',
        '--encoding UTF-8',
        '--print-media-type',
        '--enable-local-file-access',
        '--no-stop-slow-scripts',
        '--javascript-delay 1000'
    ];
    
    $command = escapeshellcmd($wkhtmltopdf) . ' ' . implode(' ', $options) . ' ' . 
               escapeshellarg($tempHtml) . ' ' . escapeshellarg($tempPdf);
    
    // Execute the command
    exec($command, $output, $returnCode);
    
    // If the PDF was successfully generated
    if ($returnCode === 0 && file_exists($tempPdf)) {
        // Set headers for the PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Prakersh_Resume.pdf"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        // Output the PDF file
        readfile($tempPdf);
        
        // Clean up temporary files
        unlink($tempHtml);
        unlink($tempPdf);
    } else {
        throw new Exception("PDF generation failed. Return code: $returnCode");
    }
} catch (Exception $e) {
    // Log error
    error_log('PDF Generation Error: ' . $e->getMessage());
    
    // Clean up any temp files
    if (isset($tempHtml) && file_exists($tempHtml)) unlink($tempHtml);
    if (isset($tempPdf) && file_exists($tempPdf)) unlink($tempPdf);
    
    // Display user-friendly error
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Error Generating PDF</h1>';
    echo '<p>We encountered a problem generating your PDF. Please try again later.</p>';
    echo '<p><a href="/">Return to Home</a></p>';
}

exit; 