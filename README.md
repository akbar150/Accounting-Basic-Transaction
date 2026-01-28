# Basic Accounting
Record all cash withdraw and deposit in a sample php base code.


# Accounting Management System - Installation Guide

Complete installation guide for the Multi-user Accounting Management Software with Deposit/Withdraw tracking, Customer Management, and Reporting.

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache 2.4+ with mod_rewrite or Nginx
- **SSL**: Recommended for production (HTTPS)

### PHP Extensions Required
```bash
php-mysql or php-mysqlnd
php-pdo
php-mbstring
php-gd
php-json
php-xml
php-ctype
php-iconv
php-intl (for currency formatting)


# Upload to your web server
cd /var/www/html/
mkdir accounting-system
cd accounting-system

# Upload all files or clone from repository
# Ensure the structure is:
# accounting-system/
# ├── api/
# ├── assets/
# ├── database/
# ├── exports/
# ├── includes/
# ├── vendor/
# ├── config.php
# └── index.html


# Install Composer if not already installed
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install TCPDF for PDF generation
composer install
# OR if composer.json exists:
composer require tecnickcom/tcpdf

## Create Database
CREATE DATABASE accounting_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'accounting_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON accounting_system.* TO 'accounting_user'@'localhost';
FLUSH PRIVILEGES;

## Import Schema

# Option A: Via Command Line
mysql -u accounting_user -p accounting_system < database/schema.sql

# Option B: Via phpMyAdmin
- Login to phpMyAdmin
- Select database
- Import tab → Choose file → database_import.sql

## Configuration

<?php
// Database Configuration
define('DB_HOST', 'localhost');          // Your database host
define('DB_NAME', 'accounting_system');   // Database name
define('DB_USER', 'accounting_user');     // Database username
define('DB_PASS', 'your_secure_password'); // Database password
define('DB_CHARSET', 'utf8mb4');

// JWT Secret - CHANGE THIS!
define('JWT_SECRET', 'your-random-secret-key-min-32-chars-long');
define('JWT_EXPIRE', 86400); // 24 hours

// Application URL
define('BASE_URL', 'https://yourdomain.com/accounting-system');
define('EXPORT_PATH', __DIR__ . '/exports/');

// Default Settings
define('CURRENCY', '৳');
define('DATE_FORMAT', 'Y-m-d');
define('TIMEZONE', 'Asia/Dhaka');

## File Permissions 
# Set ownership (replace www-data with your web server user)
chown -R www-data:www-data /var/www/html/accounting-system/

# Set permissions
chmod 755 /var/www/html/accounting-system/
chmod 755 /var/www/html/accounting-system/api/
chmod 755 /var/www/html/accounting-system/includes/
chmod 755 /var/www/html/accounting-system/exports/
chmod 755 /var/www/html/accounting-system/logs/  # If using debug logging

# Secure sensitive files
chmod 644 /var/www/html/accounting-system/config.php
chmod 644 /var/www/html/accounting-system/includes/*.php
chmod 644 /var/www/html/accounting-system/api/*.php

# Ensure exports is writable for PDF/CSV
chmod 777 /var/www/html/accounting-system/exports/  # Or 755 with proper ownership

## Web Server Configuration 
# Apache (.htaccess)

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [QSA,L]

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP Settings (if using mod_php)
php_value upload_max_filesize 64M
php_value post_max_size 64M
php_value max_execution_time 300
php_value memory_limit 256M

# Disable directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sql|log|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>


