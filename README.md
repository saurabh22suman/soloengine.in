# PHP Portfolio Website

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![CI/CD Status](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml/badge.svg)](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml)

A responsive PHP portfolio/resume website with print functionality and PDF export.

## ✨ Features

- Responsive design using Bootstrap 5
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
4. Customize the content in the `includes` directory to match your personal information.
5. Replace the placeholder images in `assets/images` with your own images.

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
│   ├── profile.php        # Profile section
│   ├── experience.php     # Work experience section
│   ├── education.php      # Education section
│   ├── skills.php         # Skills section
│   └── projects.php       # Projects section
│
├── assets/
│   └── images/            # Image files
│
├── generate-pdf-wk.php    # PDF generation script
├── wkhtmltopdf-setup.md   # wkhtmltopdf installation guide
└── index.php              # Main page
```

## 🛠️ Customization

1. Edit the files in the `includes` directory to update your personal information
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

- Web server with PHP support
- wkhtmltopdf (for PDF export functionality)
- Modern web browser

## 🔄 CI/CD Pipeline

This project uses GitHub Actions for continuous integration and deployment. The pipeline:

- Automatically tests PHP code for syntax errors
- Verifies code quality with PSR-12 standards
- Lints CSS and JavaScript files
- Runs integration tests by starting a PHP server and checking responses with wget
- Creates a downloadable build artifact on successful code checks

For more details, see the [CI/CD documentation](CICD.md).

## 📧 Contact

[Prakersh Maheshwari] - [prakersh@live.com]

Project Link: [https://github.com/prakersh/prakersh.in.git](https://github.com/prakersh/prakersh.in.git) 