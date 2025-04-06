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
- Easily customizable

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
   - Use the admin panel to customize your content
   - Change the default password in the Settings tab for security

## 📁 File Structure

```
portfolio/
│
├── css/
│   ├── style.css          # Main stylesheet
│   └── print.css          # Print-specific styles
│
├── js/
│   └── main.js            # JavaScript functionality
│
├── includes/
│   ├── header.php         # Page header
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
├── admin.php              # Admin panel
├── init_db.php            # Database initialization script
├── reset_db.php           # Database reset script (development only)
├── generate-pdf-wk.php    # PDF generation script
├── DATABASE_README.md     # Database documentation
├── wkhtmltopdf-setup.md   # wkhtmltopdf installation guide
└── index.php              # Main page
```

## 🛠️ Customization

1. Log in to the admin panel at `admin.php` to update your personal information
2. Modify the CSS in `css/style.css` to change the appearance
3. Update the print styles in `css/print.css` if needed
4. Replace placeholder images with your own project screenshots and profile photo

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