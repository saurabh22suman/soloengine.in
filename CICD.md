# CI/CD Pipeline Documentation

This document describes the Continuous Integration and Continuous Deployment (CI/CD) pipeline implemented for the PHP Portfolio website.

## Overview

The CI/CD pipeline automates the process of testing, linting, verifying website functionality, and creating build artifacts for the website whenever changes are pushed to the repository. This ensures code quality, functionality, and prepares the site for deployment.

## Pipeline Configuration

The pipeline is implemented using GitHub Actions and defined in `.github/workflows/php-workflow.yml`. It consists of four main jobs:

### 1. Test Job

This job checks the PHP code quality and correctness:

- Sets up PHP 8.1 with necessary extensions (including SQLite support)
- Validates composer configuration (if present)
- Checks for PHP syntax errors
- Runs PHP Code Sniffer to enforce PSR-12 coding standards

### 2. Lint Job

This job ensures the front-end code meets quality standards:

- Sets up Node.js environment
- Installs stylelint and ESLint
- Verifies CSS directory exists (creates it if missing)
- Lints CSS files using stylelint
- Lints JavaScript files using ESLint

### 3. Integration Job

This job verifies that the website functions correctly when served and implements comprehensive security testing:

#### Basic Functionality Tests:
- Sets up PHP 8.1 environment with SQLite support
- Creates necessary data directory with proper permissions
- Starts a PHP development server
- Waits for the server to be ready
- Verifies the website is accessible
- Checks that the server responds with a 200 status code
- Tests database initialization script (init_db.php)
- Validates admin page accessibility

#### Security & Authentication Tests:
- **Protected Admin Endpoints**: Verifies that `reset_db.php`, `check_theme.php`, and `migrate_passwords.php` require admin authentication
- **Password Hashing Verification**: Confirms passwords are stored using bcrypt hashing (not plain text)
- **Admin Authentication Flows**: Tests login page accessibility and invalid login rejection
- **CSRF Token Implementation**: Verifies CSRF tokens are present in admin forms
- **Input Validation & Sanitization**: Tests theme parameter validation and XSS prevention
- **Session Management**: Validates session cookie security settings

#### Database Protection Tests:
- **Backup Functionality**: Tests database backup creation and directory structure
- **Backup Rotation**: Verifies backup rotation functionality (keeping last 3 backups)
- **SQL Injection Prevention**: Tests protection against SQL injection in login forms and parameters
- **Information Disclosure Prevention**: Ensures error pages don't expose sensitive system information or database schema

#### Advanced Security Tests:
- **Session Security**: Tests session cookie flags and security settings
- **Error Handling**: Verifies that database errors don't expose schema information
- **Access Control**: Confirms unauthorized access attempts are properly blocked and logged

### 4. Build Job

This job creates a deployable artifact of the website:

- Only runs when changes are pushed to the main/master branch
- Only runs if the test, lint, and integration jobs pass
- Creates build directory with data subdirectory for the database
- Copies all relevant files to the build directory, including:
  - PHP files (index.php, admin.php, init_db.php, reset_db.php, etc.)
  - Asset directories (css, js, includes, assets)
  - Documentation files (.md)
- Sets proper permissions for the data directory
- Creates and uploads a build artifact that can be accessed from GitHub Actions

## Fault Tolerance

The CI/CD pipeline includes several fault-tolerance mechanisms:

- Optional composer validation (`|| true`) to handle projects without composer
- CSS directory verification with automatic creation if missing
- Data directory creation with proper permissions
- Lenient content verification that focuses on server response rather than specific text
- Safe artifact creation with fallback for missing directories

## Database Support

The CI/CD pipeline has been updated to support the SQLite database functionality and comprehensive security testing:

- PHP environment includes SQLite3 and PDO_SQLite extensions
- Integration tests verify database initialization works correctly
- **Security Testing**: Comprehensive security tests including:
  - Password hashing verification (bcrypt)
  - SQL injection prevention testing
  - Admin authentication requirement verification
  - CSRF token implementation testing
  - Input validation and sanitization testing
  - Database backup and rotation functionality testing
- **Database Protection**: Tests backup creation, rotation, and security measures
- Build artifacts include the data directory with proper permissions
- Admin panel functionality is verified during testing

## Downloading Build Artifacts

After the workflow has run successfully, you can download the build artifact:

1. Go to your GitHub repository
2. Click on the "Actions" tab
3. Select the successful workflow run
4. Scroll down to the "Artifacts" section
5. Click on the "website-build" artifact to download it

## Manual Deployment

After downloading the artifact, you can manually deploy the website by uploading the contents to your web server using:

- FTP client
- SSH and command line tools
- Web hosting control panel upload function

When deploying, ensure your server:
- Has PHP 7.0+ with SQLite support enabled
- Provides write permissions for the data directory
- Has wkhtmltopdf installed (if using PDF export functionality)

## Post-Deployment Steps

After deploying the artifact, you should:

1. Access init_db.php through your browser to initialize the database
2. Log in to admin.php with default credentials (admin/admin123)
3. Change the default admin password immediately
4. Consider restricting access to sensitive files via .htaccess

## Comprehensive Security Testing Coverage

The CI/CD pipeline now includes extensive security testing that covers all aspects of the security enhancements implemented:

### Authentication & Authorization Testing
- **Admin Authentication Flows**: Verifies login page functionality and invalid login rejection
- **Session Management**: Tests session cookie security and timeout functionality
- **Protected Endpoints**: Confirms all admin-only operations require proper authentication
- **Access Control**: Validates that unauthorized access attempts are properly blocked

### Database Security Testing
- **Password Security**: Verifies passwords are hashed using bcrypt (not stored in plain text)
- **SQL Injection Prevention**: Tests protection against SQL injection attacks in forms and parameters
- **Database Backup Protection**: Tests backup creation, rotation, and security measures
- **Schema Protection**: Ensures database errors don't expose sensitive schema information

### Input Security Testing
- **CSRF Protection**: Verifies CSRF tokens are implemented in admin forms
- **Input Validation**: Tests theme parameter validation and sanitization
- **XSS Prevention**: Basic testing for cross-site scripting prevention
- **Parameter Security**: Tests protection against malicious parameter manipulation

### Information Security Testing
- **Error Handling**: Ensures error pages don't expose sensitive system information
- **Data Exposure Prevention**: Confirms sensitive data is not leaked in responses
- **Path Traversal Protection**: Tests protection against directory traversal attacks

### Operational Security Testing
- **Backup Functionality**: Verifies database backup creation and rotation (keeping last 3 backups)
- **Audit Trail**: Tests security logging and monitoring capabilities
- **Session Timeout**: Validates session management and timeout functionality

This comprehensive testing ensures that all security improvements are functioning correctly and the application maintains a strong security posture.

## Extending the Pipeline

If you want to add automated deployment in the future, you can extend the workflow by:

1. Adding additional deployment jobs that use your preferred deployment method
2. Setting up appropriate secrets for the deployment method, if required
3. Modifying the build step to prepare the artifact in the format needed by your deployment method

## Troubleshooting

If the CI/CD pipeline fails:

1. Check the GitHub Actions logs for specific error messages
2. Fix any code quality issues identified in the test or lint jobs
3. If the integration tests fail, check that your web server is properly configured
4. Ensure required directories (css, js, includes, assets, data) exist in your repository
5. Review the file structure to ensure it matches the expected layout
6. Verify that PHP files related to database interaction are working correctly 