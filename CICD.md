# CI/CD Pipeline Documentation

This document describes the Continuous Integration and Continuous Deployment (CI/CD) pipeline implemented for the PHP Portfolio website.

## Overview

The CI/CD pipeline automates the process of testing, linting, verifying website functionality, and creating build artifacts for the website whenever changes are pushed to the repository. This ensures code quality, functionality, and prepares the site for deployment.

## Pipeline Configuration

The pipeline is implemented using GitHub Actions and defined in `.github/workflows/php-workflow.yml`. It consists of four main jobs:

### 1. Test Job

This job checks the PHP code quality and correctness:

- Sets up PHP 8.1 with necessary extensions
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

This job verifies that the website functions correctly when served:

- Sets up PHP 8.1 environment
- Starts a PHP development server
- Waits for the server to be ready
- Verifies the website is accessible
- Checks that the server responds with a 200 status code

### 4. Build Job

This job creates a deployable artifact of the website:

- Only runs when changes are pushed to the main/master branch
- Only runs if the test, lint, and integration jobs pass
- Copies the relevant files to a build directory
- Creates and uploads a build artifact that can be accessed from GitHub Actions

## Fault Tolerance

The CI/CD pipeline includes several fault-tolerance mechanisms:

- Optional composer validation (`|| true`) to handle projects without composer
- CSS directory verification with automatic creation if missing
- Lenient content verification that focuses on server response rather than specific text
- Safe artifact creation with fallback for missing directories

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
4. Ensure required directories (css, js, includes, assets) exist in your repository
5. Review the file structure to ensure it matches the expected layout 