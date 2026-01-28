# Deployment Guide

## Requirements
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx with mod_rewrite
- Composer (for PDF libraries)

## Installation Steps

1. **Clone/Download** files to web root (e.g., /var/www/accounting-system/)

2. **Database Setup**
   ```bash
   mysql -u root -p &lt; database/schema.sql