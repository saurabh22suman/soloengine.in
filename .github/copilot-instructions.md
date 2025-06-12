# GitHub Copilot Instructions for PHP Portfolio Website

This document provides specific guidelines for GitHub Copilot when working on this PHP portfolio website with SQLite database integration.

## 🔒 Security First - PUBLIC FACING APPLICATION

⚠️ **CRITICAL**: This is a **PUBLIC-FACING** application accessible on the internet. Security is not optional - it's mandatory.

### 🛡️ Authentication & Authorization (MANDATORY)
- **ALL** Create, Update, Delete, and Reset operations MUST be behind admin authentication
- **NEVER** trust user input - always verify session authentication before ANY data modification
- Implement session timeout and proper logout functionality
- Use strong password requirements 
- Implement rate limiting for login attempts to prevent brute force attacks
- Log all admin access attempts for security monitoring

### 🗄️ Database Security (ZERO TOLERANCE FOR SQL INJECTION)
- **ALWAYS** use PDO prepared statements with parameter binding - NO EXCEPTIONS
- Validate data types, length, and format constraints before database operations
- Implement proper error handling without exposing system details to users
- Ensure database file permissions are secure (readable/writable by web server only)
- Never store passwords in plain text - use proper hashing (password_hash/password_verify)
- Implement database connection limits and timeout settings

### 🌐 Web Application Security
- **Input Validation**: Validate and sanitize ALL user inputs (GET, POST, COOKIE, SESSION)
- **Output Encoding**: Escape all output to prevent XSS attacks
- **CSRF Protection**: Implement CSRF tokens for state-changing operations
- **HTTP Headers**: Set security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- **File Upload Security**: If handling file uploads, validate file types, sizes, and scan for malware
- **Directory Traversal**: Prevent path traversal attacks in file operations
- **Information Disclosure**: Never expose sensitive information in error messages, logs, or comments

### 🔐 Session Security
- Use secure session configuration (httponly, secure flags)
- Implement proper session regeneration after login
- Set appropriate session timeout values
- Clear sessions completely on logout
- Validate session integrity and user agent consistency

### 🔒 Secure Code Patterns (MANDATORY)

#### Admin Route Protection (ENHANCED)
```php
// MANDATORY: Always start with session security
session_start();

// Regenerate session ID on sensitive operations
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    header('Location: admin.php');
    exit('Access denied');
}

// Additional security: Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_destroy();
    header('Location: admin.php?timeout=1');
    exit('Session expired');
}
$_SESSION['last_activity'] = time();
```

#### Database Operations (ENHANCED)
```php
// MANDATORY: Always use prepared statements with explicit types
try {
    $stmt = $pdo->prepare("UPDATE profile SET name = ?, email = ? WHERE id = ?");
    $stmt->bindParam(1, $sanitized_name, PDO::PARAM_STR);
    $stmt->bindParam(2, $sanitized_email, PDO::PARAM_STR);
    $stmt->bindParam(3, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Log successful operations for audit trail
    error_log("Profile updated by admin: " . $_SESSION['admin_id']);
} catch (PDOException $e) {
    // NEVER expose database errors to users
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    exit('Operation failed. Please try again.');
}
```

#### Input Validation & Sanitization (MANDATORY)
```php
// Example: Comprehensive input validation
function validateAndSanitizeInput($input, $type, $maxLength = null) {
    // Remove potential XSS
    $input = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    
    switch ($type) {
        case 'email':
            if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            break;
        case 'url':
            if (!filter_var($input, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid URL format');
            }
            break;
        case 'string':
            if ($maxLength && strlen($input) > $maxLength) {
                throw new InvalidArgumentException('Input too long');
            }
            // Remove potential SQL injection attempts
            $input = preg_replace('/[\'\";\\\]/', '', $input);
            break;
    }
    
    return $input;
}
```

### 🚨 Security Testing Requirements (PUBLIC APPLICATION)
Before ANY code changes go live:

1. **Authentication Testing**
   - Test admin login with correct/incorrect credentials
   - Verify session timeout works correctly
   - Test logout functionality completely clears sessions
   - Attempt to access admin routes without authentication
   - Test with expired/invalid session tokens

2. **Input Security Testing**
   - Test with SQL injection payloads in all input fields
   - Test XSS attempts in all user inputs
   - Test file upload security (if applicable)
   - Test with extremely long inputs to check for buffer overflows
   - Test with special characters and Unicode

3. **Network Security Testing**
   - Verify HTTPS is enforced (if applicable)
   - Check for information disclosure in HTTP headers
   - Test for clickjacking vulnerabilities
   - Verify no sensitive data in browser cache

4. **Error Handling Testing**
   - Ensure database errors don't expose schema information
   - Verify stack traces are not shown to users
   - Test error pages don't reveal system paths or versions

## 🧪 Comprehensive Testing Requirements

### Before Any Pull Request
1. **Database Operations Testing**
   - Test ALL CRUD operations (Create, Read, Update, Delete)
   - Verify admin authentication works correctly
   - Test password change functionality
   - Ensure database initialization and reset work properly

2. **Frontend Functionality Testing**
   - Test all theme switches and verify styling consistency
   - Verify print functionality works correctly
   - Test PDF export functionality (if wkhtmltopdf is installed)
   - Check responsive design on different screen sizes
   - Validate form submissions and error handling

3. **Security Testing**
   - Attempt to access admin routes without authentication
   - Test with malicious inputs to ensure proper sanitization
   - Verify session timeout and logout functionality
   - Test password requirements and validation

4. **Cross-Browser Testing**
   - Test on Chrome, Firefox, Safari, and Edge
   - Verify CSS compatibility across browsers
   - Check JavaScript functionality

### Testing Checklist Template:
```
- [ ] Admin login/logout works correctly
- [ ] All CRUD operations function properly
- [ ] Theme switching works without errors
- [ ] Print layout is properly formatted
- [ ] PDF export generates correctly
- [ ] Responsive design works on mobile/tablet
- [ ] Forms validate properly and show appropriate errors
- [ ] Database operations are secure and use prepared statements
- [ ] Unauthorized access is properly blocked
- [ ] Cross-browser compatibility verified
```

## 💭 Think Before You Code

### Change Philosophy
- **95% Confidence Rule**: Only implement changes when you are 95% confident they are necessary
- **Minimal Changes**: Make the smallest possible change that solves the problem
- **Impact Assessment**: Consider how changes affect existing functionality
- **Backward Compatibility**: Ensure changes don't break existing features

### Before Making Changes:
1. **Analyze the Problem**: Understand the root cause, not just symptoms
2. **Review Existing Code**: Study current implementation patterns
3. **Consider Alternatives**: Evaluate multiple solutions before choosing
4. **Plan Dependencies**: Identify what other components might be affected
5. **Validate Necessity**: Ask "Is this change truly required?"

### Questions to Ask Yourself:
- Does this change solve a real problem or add genuine value?
- Can the same result be achieved with less code modification?
- Will this change maintain backward compatibility?
- Does this follow the existing code patterns and architecture?
- Have I considered edge cases and potential side effects?

## 🎨 UI Consistency Guidelines

### Follow Existing Patterns
- **Bootstrap Classes**: Use existing Bootstrap 5 classes and utilities
- **Theme Variables**: Utilize CSS custom properties defined in theme files
- **Component Structure**: Follow established HTML structure patterns
- **Responsive Design**: Maintain existing breakpoint behavior

### CSS Guidelines
- **Theme Support**: Ensure new styles work with ALL available themes
- **CSS Variables**: Use existing CSS custom properties (`:root` variables)
- **Media Queries**: Follow existing responsive design patterns
- **Print Styles**: Maintain print-friendly layouts for PDF export

### Form Consistency
```html
<!-- Follow this pattern for forms -->
<div class="mb-3">
    <label for="inputId" class="form-label">Label Text</label>
    <input type="text" class="form-control" id="inputId" name="fieldName" required>
    <div class="form-text">Helper text if needed</div>
</div>
```

### Button Styling
```html
<!-- Primary actions -->
<button type="submit" class="btn btn-primary">Save Changes</button>

<!-- Secondary actions -->
<button type="button" class="btn btn-secondary">Cancel</button>

<!-- Dangerous actions -->
<button type="button" class="btn btn-danger">Delete</button>
```

### Card Layout Pattern
```html
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Section Title</h5>
    </div>
    <div class="card-body">
        <!-- Content here -->
    </div>
</div>
```

## 📚 Documentation Requirements

### Always Update Documentation After Changes

1. **Code Comments**
   - Add inline comments for complex logic
   - Document function parameters and return values
   - Explain security considerations where applicable

2. **README.md Updates**
   - Update feature lists if new functionality is added
   - Modify installation instructions if steps change
   - Update requirements if new dependencies are added

3. **DATABASE_README.md Updates**
   - Document any database schema changes
   - Update table structure descriptions
   - Add new configuration options

4. **CHANGELOG or Release Notes**
   - Document breaking changes
   - List new features and improvements
   - Note security fixes or updates

### Documentation Checklist:
```
- [ ] Updated relevant README sections
- [ ] Added/updated code comments
- [ ] Documented new database changes
- [ ] Updated configuration examples
- [ ] Added troubleshooting notes if applicable
- [ ] Updated feature lists and capabilities
```

## 🗂️ Project-Specific Patterns

### Database Connection Pattern
```php
// Always use the established connection pattern
require_once 'includes/db_connect.php';
$pdo = getDbConnection();
```

### Admin Route Protection
```php
// Standard admin check pattern
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}
```

### Theme Integration
- All new CSS must work with existing theme system
- Use CSS custom properties for colors and styling
- Test with all available themes before committing

### File Organization
- Keep PHP includes in `includes/` directory
- Store CSS themes in `css/` directory with `theme-` prefix
- Place JavaScript in `js/` directory
- Store assets in `assets/` subdirectories

## 🚨 Red Flags to Avoid

### Security Red Flags (ZERO TOLERANCE)
- **CRITICAL**: Direct SQL queries without prepared statements
- **CRITICAL**: Unsanitized user input reaching database or output
- **CRITICAL**: Missing authentication checks on ANY admin functionality  
- **CRITICAL**: Exposed error messages with system information
- **CRITICAL**: Hardcoded credentials or API keys in code
- **HIGH**: Missing CSRF protection on state-changing operations
- **HIGH**: Inadequate session security (missing httponly, secure flags)
- **HIGH**: Information disclosure through comments or debug output
- **MEDIUM**: Missing input length validation
- **MEDIUM**: Weak password requirements or storage

### Code Quality Red Flags
- Duplicate code that could be refactored
- Large functions that do multiple things
- Missing error handling
- Inconsistent coding style
- Breaking existing functionality

### UI/UX Red Flags
- Inconsistent styling with existing themes
- Breaking responsive design
- Poor accessibility (missing alt text, labels, etc.)
- Non-intuitive user interface changes
- Breaking print/PDF functionality

## 📋 Pre-Commit Checklist (MANDATORY SECURITY CHECKS)

Before submitting any code changes:

```
🔒 SECURITY VERIFICATION (MANDATORY):
- [ ] All admin operations require authentication verification
- [ ] All database queries use prepared statements with parameter binding
- [ ] All user inputs are validated and sanitized
- [ ] No sensitive information exposed in error messages or logs
- [ ] Session security properly implemented (timeouts, regeneration)
- [ ] CSRF protection implemented for state-changing operations
- [ ] No hardcoded credentials or sensitive data in code
- [ ] Security headers properly set
- [ ] Attempted unauthorized access properly blocked and logged

🧪 TESTING VERIFICATION:
- [ ] Code follows project security patterns
- [ ] All CRUD operations tested thoroughly
- [ ] Admin authentication verified with invalid/expired sessions
- [ ] SQL injection testing performed on all inputs
- [ ] XSS testing performed on all user inputs
- [ ] UI consistency maintained across all themes
- [ ] Print functionality still works
- [ ] Error handling is user-friendly (no system info exposed)
- [ ] Documentation updated appropriately
- [ ] Cross-browser testing completed
- [ ] Mobile responsiveness verified
- [ ] Changes are minimal and necessary
- [ ] Backward compatibility maintained
```

## 🎯 Success Metrics

A successful contribution should:
- Solve the intended problem with minimal code changes
- Maintain or improve security posture (NEVER compromise security)
- Work seamlessly with all existing themes
- Pass all testing requirements (including comprehensive security testing)
- Include appropriate documentation updates
- Follow established code patterns and conventions

Remember: **Security & Quality over quantity**. A small, well-tested, secure change is infinitely better than a large, untested modification that breaks existing functionality or introduces security vulnerabilities.

## ⚠️ FINAL SECURITY REMINDER

This application is **PUBLIC-FACING** and accessible from the internet. Every line of code you write could potentially be exploited by malicious actors. Always assume that attackers will:

- Try to bypass authentication
- Inject malicious SQL into every input field
- Attempt XSS attacks through any user input
- Try to access admin functions directly
- Look for information disclosure in error messages
- Attempt brute force attacks on login forms
- Try to exploit file upload functionality
- Search for hardcoded credentials in the code

**NEVER** compromise on security for the sake of convenience or quick fixes. When in doubt, choose the more secure approach.
