# PHP Portfolio Website

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![CI/CD Status](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml/badge.svg)](https://github.com/prakersh/prakersh.in/actions/workflows/php-workflow.yml)

A responsive PHP portfolio/resume website with print functionality.

## ✨ Features

- Responsive design using Bootstrap 5
- Print-friendly layout
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
3. Customize the content in the `includes` directory to match your personal information.
4. Replace the placeholder images in `assets/images` with your own images.

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
└── index.php              # Main page
```

## 🛠️ Customization

1. Edit the files in the `includes` directory to update your personal information
2. Modify the CSS in `css/style.css` to change the appearance
3. Update the print styles in `css/print.css` if needed
4. Replace placeholder images with your own project screenshots and profile photo

## 🖨️ Printing

The portfolio includes a print button that allows visitors to print your resume. The print.css file ensures that the printed version is optimized for paper.

## 📋 Requirements

- Web server with PHP support
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