# SQLite Database Implementation for Resume Website

This project has been updated to use SQLite for dynamic data management. Here's how to work with it:

## Database Structure

The database is stored in `data/resume.db` and consists of the following tables:

- `profile`: Contains personal information like name, contact details, etc.
- `experience`: Work experience entries
- `education`: Educational background
- `skills`: Technical and other skills
- `achievements`: Notable achievements and certifications
- `projects`: Projects showcased in the portfolio
- `admin_settings`: Stores admin credentials and settings

## Getting Started

1. **Initialize the database**:
   - Access `init_db.php` in your browser to create and populate the database with default values
   - The database will be automatically created if it doesn't exist
   - Alternatively, use `reset_db.php` during development to completely reset the database

2. **Admin Panel**:
   - Access `admin.php` in your browser
   - Default credentials (for demo only):
     - Username: `admin`
     - Password: `admin123`
   - Use the admin panel to update content without modifying code
   - Navigate to the Settings tab to change your admin password

## Technical Implementation

- The database connection is handled by `includes/db_connect.php`
- Default data is populated from `includes/populate_db.php`
- Each section of the site has been updated to pull data from the database
- Admin authentication uses the `admin_settings` table for secure credential management

## Security Notes

- This implementation is for demonstration purposes
- In a production environment:
  - Use proper password hashing for the admin panel
  - Add more robust authentication
  - Consider adding prepared statements for all database queries
  - Implement proper input validation
  - Add rate limiting for login attempts

## Password Management

- The admin password can be changed via the Settings tab in the admin panel
- Password requirements: minimum 6 characters
- After changing your password, you'll be logged out and need to log in again with the new password

## Backup

To backup your data, simply copy the `data/resume.db` file. This contains all your content.

## PHP Requirements

- PHP 7.0 or higher
- PDO SQLite extension enabled 