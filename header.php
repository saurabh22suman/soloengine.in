    <!-- Theme CSS -->
    <?php
    // Set default theme if not set
    if(!isset($theme) || empty($theme)) {
        $theme = 'light';
    }
    
    // List of available themes
    $available_themes = [
        'light', 'dark', 'blue', 'green', 'peach', 'neon', 
        'minimal', 'watercolor', 'vscode', 'matrix', 'retro', 'ubuntu'
    ];
    
    // Validate theme
    if(!in_array($theme, $available_themes)) {
        $theme = 'light';
    }
    
    // Load theme CSS
    echo '<link rel="stylesheet" href="css/theme-' . $theme . '.css">';
    ?> 