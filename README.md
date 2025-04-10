# PHP Portfolio Website with SQLite Database

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![CI/CD Status](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml/badge.svg)](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml)

A responsive PHP portfolio/resume website with SQLite database integration, print functionality, and PDF export.

## ✨ Features

- Responsive design using Bootstrap 5
- Dynamic content management with SQLite database
- Admin panel for content management
- Secure authentication system
- Print-friendly layout
- PDF resume export using wkhtmltopdf
- Projects showcase
- Skills visualization
- Work experience timeline
- Education history
- Modern and clean UI
- Multiple customizable themes
- Live theme preview in admin panel
- Easily customizable

## 🎨 Available Themes

The website comes with several built-in themes:

- **Light** - Clean, minimal light theme (default)
- **Dark** - Sleek dark mode theme
- **Blue** - Professional blue-based theme
- **Green** - Fresh nature-inspired theme
- **Peach** - Warm, vibrant theme with gradients and subtle animations
- **Neon** - Bold cyberpunk-inspired theme with glowing effects and dynamic animations
- **Minimal** - Ultra-clean minimalist design focused on typography and whitespace
- **Watercolor** - Artistic theme with painterly effects and creative styling
- **VSCode** - Developer-inspired theme based on VS Code dark editor
- **GitHub** - Theme inspired by GitHub interface
- **Retro** - Nostalgic computer terminal-inspired theme
- **Ubuntu** - Theme inspired by Ubuntu OS design
- **Matrix** - Hacker-style matrix theme

Themes can be selected from the admin panel and affect the entire website's appearance.

## 🚀 Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/prakersh/prakersh.in.git
   ```
2. Upload all files to your web server that supports PHP.
3. Install wkhtmltopdf for PDF generation:
   - See [wkhtmltopdf-setup.md](wkhtmltopdf-setup.md) for detailed installation instructions
4. Initialize the database:
   - Access `init_db.php` through your browser to create and populate the database
   - Ensure the `data` directory is writable by the web server
5. Access the admin panel at `admin.php`:
   - Default credentials: username `admin`, password `admin123`
   - Use the admin panel to customize your content and select your preferred theme
   - Change the default password in the Settings tab for security

## 📁 File Structure

```
portfolio/
│
├── css/
│   ├── style.css          # Main stylesheet
│   ├── print.css          # Print-specific styles
│   ├── theme-light.css    # Light theme (default)
│   ├── theme-dark.css     # Dark theme
│   ├── theme-blue.css     # Blue theme
│   ├── theme-green.css    # Green theme
│   ├── theme-peach.css    # Peach theme
│   ├── theme-neon.css     # Neon theme
│   ├── theme-minimal.css  # Minimal theme
│   ├── theme-watercolor.css # Watercolor theme
│   ├── theme-vscode.css   # VSCode-inspired theme
│   ├── theme-github.css   # GitHub-inspired theme
│   ├── theme-retro.css    # Retro computer theme
│   └── theme-ubuntu.css   # Ubuntu OS-inspired theme
│
├── js/
│   └── main.js            # JavaScript functionality
│
├── includes/
│   ├── header.php         # Page header (with theme handling)
│   ├── footer.php         # Page footer
│   ├── db_connect.php     # Database connection
│   ├── populate_db.php    # Default database content
│   ├── profile.php        # Profile section
│   ├── experience.php     # Work experience section
│   ├── education.php      # Education section
│   ├── skills.php         # Skills section
│   └── projects.php       # Projects section
│
├── assets/
│   └── images/            # Image files
│
├── data/
│   └── resume.db          # SQLite database (auto-created)
│
├── admin.php              # Admin panel with theme selection
├── init_db.php            # Database initialization script
├── reset_db.php           # Database reset script (development only)
├── update_db.php          # Database update script (adds theme column)
├── check_theme.php        # Theme verification script
├── generate-pdf-wk.php    # PDF generation script
├── DATABASE_README.md     # Database documentation
├── wkhtmltopdf-setup.md   # wkhtmltopdf installation guide
└── index.php              # Main page
```

## 🛠️ Customization

1. Log in to the admin panel at `admin.php` to update your personal information
2. Select your preferred theme from the theme dropdown in the admin panel
3. Preview how different themes look using the live preview feature
4. Modify the CSS in `css/style.css` or theme files to change the appearance
5. Update the print styles in `css/print.css` if needed
6. Replace placeholder images with your own project screenshots and profile photo

## 🎨 Theme System

### Selecting a Theme
1. Log in to the admin panel
2. Navigate to the theme selection dropdown
3. Choose from the available themes
4. Use the live preview to see how elements will look with the selected theme
5. Save your changes to apply the theme to your entire website

### Creating Custom Themes
You can create your own custom themes:

1. Duplicate one of the existing theme CSS files in the `css/` directory
2. Rename it to `theme-yourthemename.css`
3. Modify the CSS variables and styles to create your unique look
4. Add your theme name to the available themes list in `header.php`
5. Add your theme as an option in the theme dropdown in `admin.php`

### Theme Structure
Each theme uses CSS variables to define colors, fonts, and other design elements:

```css
:root {
    --primary-color: #value;
    --secondary-color: #value;
    --accent-color: #value;
    --text-color: #value;
    --light-bg: #value;
    /* Additional variables */
}
```

### Theme Troubleshooting
If you encounter issues with themes:

1. Ensure the `theme` column exists in your database by running `update_db.php`
2. Verify theme values with `check_theme.php` - this will set default themes if missing
3. Check that all theme CSS files are properly loaded in `header.php`
4. Make sure each theme CSS file defines all required CSS variables

### Theme Preview
The admin panel includes a live theme preview section that shows:
- Button styling
- Card components
- Progress bars
- Text formatting

This helps you visualize how each theme will affect different UI elements before applying it.

## 🖨️ Printing and PDF Export

### Printing
The portfolio includes a print button that allows visitors to print your resume. The print.css file ensures that the printed version is optimized for paper.

### PDF Export
The site offers PDF resume generation using wkhtmltopdf, which ensures the exported PDF looks exactly like the web version with print styles applied:

1. Click the "Download Resume" button to generate a PDF
2. The PDF generation script creates a temporary HTML file with all your resume content
3. wkhtmltopdf renders this HTML with print.css applied
4. The resulting PDF is delivered to the user for download

#### Requirements for PDF Export
- wkhtmltopdf must be installed on the server
- See [wkhtmltopdf-setup.md](wkhtmltopdf-setup.md) for installation instructions

## 📋 Requirements

- Web server with PHP support (7.0 or higher)
- PDO SQLite extension enabled
- File write permissions for the `data` directory
- wkhtmltopdf (for PDF export functionality)
- Modern web browser

## 🚀 Deployment Guide

### Deploying to a Fresh Server

1. **Server Requirements**:
   - PHP 7.0 or higher
   - PDO SQLite extension
   - Write permissions for web server user
   - wkhtmltopdf (for PDF generation)

2. **Set Up Process**:
   ```bash
   # Clone the repository
   git clone https://github.com/prakersh/prakersh.in.git
   cd prakersh.in
   
   # Ensure data directory exists and is writable
   mkdir -p data
   chmod 755 data
   
   # Set proper permissions for web files
   chmod -R 755 .
   chmod 644 *.php *.md *.json
   ```

3. **Database Initialization**:
   - Navigate to `http://your-server/init_db.php` in a browser
   - This will create and populate the SQLite database

4. **Admin Access**:
   - Navigate to `http://your-server/admin.php`
   - Log in with default credentials:
     - Username: `admin`
     - Password: `admin123`
   - Immediately change the default password in the Settings tab

5. **Production Security**:
   - Consider setting up HTTPS for secure communication
   - Restrict direct access to the database file with .htaccess
   - Add IP restrictions for admin access if applicable

### Shared Hosting Deployment

For shared hosting environments:

1. Upload all files via FTP to your web hosting
2. Ensure PHP 7.0+ is available and SQLite is enabled
3. Set proper permissions:
   - Directories: 755
   - PHP files: 644
   - data directory: 755
4. Follow steps 3-5 from the section above

## 🔄 CI/CD Pipeline

This project uses GitHub Actions for continuous integration and deployment. The pipeline:

- Automatically tests PHP code for syntax errors
- Verifies code quality with PSR-12 standards
- Lints CSS and JavaScript files
- Runs integration tests by starting a PHP server and checking responses with wget
- Creates a downloadable build artifact on successful code checks

For more details, see the [CI/CD documentation](CICD.md).

## 📝 Database Documentation

For detailed information about the database structure and management, see [DATABASE_README.md](DATABASE_README.md).

## 📧 Contact

[Prakersh Maheshwari] - [prakersh@live.com]

Project Link: [https://github.com/prakersh/prakersh.in.git](https://github.com/prakersh/prakersh.in.git) 