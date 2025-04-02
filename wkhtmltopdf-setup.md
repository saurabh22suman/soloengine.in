# wkhtmltopdf Installation Guide

This document explains how to install wkhtmltopdf, which is required for PDF generation.

## Why wkhtmltopdf?

wkhtmltopdf uses WebKit to render HTML with CSS, ensuring your PDF looks exactly like your web page with print styles applied.

## Installation Instructions

### Ubuntu/Debian:

```bash
sudo apt-get update
sudo apt-get install wkhtmltopdf
```

### Windows:

1. Download the installer from [wkhtmltopdf.org/downloads.html](https://wkhtmltopdf.org/downloads.html)
2. Run the installer
3. Add the installation directory to your PATH environment variable

### macOS:

```bash
brew install wkhtmltopdf
```

## Verifying Installation

After installation, verify it's working by running:

```bash
wkhtmltopdf --version
```

## Troubleshooting

If you encounter errors:

1. Make sure wkhtmltopdf is installed in one of these locations:
   - `/usr/bin/wkhtmltopdf` (Linux)
   - `/usr/local/bin/wkhtmltopdf` (macOS)
2. Ensure the server has permissions to execute wkhtmltopdf
3. If you get blank pages, check that `--enable-local-file-access` is set in the command 