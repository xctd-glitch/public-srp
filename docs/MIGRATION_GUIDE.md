# Migration Guide: From Old Structure to New Refactored Structure

## Pendahuluan

Panduan ini akan membantu Anda untuk migrate dari struktur lama (monolitik) ke struktur baru (MVC-based) dari aplikasi SRP.

## Pre-Migration Checklist

- [ ] Backup semua file di direktori `public/`
- [ ] Backup database MySQL
- [ ] Catat konfigurasi web server (Apache/Nginx)
- [ ] Catat semua environment variables yang digunakan
- [ ] Test aplikasi lama untuk memastikan semua fitur berfungsi

## Step-by-Step Migration

### Step 1: Backup

```bash
# Backup files
cp -r E:\.claude\full-SRP\public E:\.claude\full-SRP\public_backup_$(date +%Y%m%d)

# Backup database
mysqldump -u root -p srp > srp_backup_$(date +%Y%m%d).sql
```

### Step 2: Update Web Server Configuration

#### Apache Configuration

**File**: `httpd.conf` atau Virtual Host config

**Sebelum:**
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "E:/.claude/full-SRP/public"

    <Directory "E:/.claude/full-SRP/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Sesudah:**
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "E:/.claude/full-SRP/public/public_html"

    <Directory "E:/.claude/full-SRP/public/public_html">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Deny access to src directory
    <Directory "E:/.claude/full-SRP/public/src">
        Require all denied
    </Directory>
</VirtualHost>
```

#### Nginx Configuration

**File**: `/etc/nginx/sites-available/srp.conf`

**Sebelum:**
```nginx
server {
    listen 80;
    server_name localhost;
    root E:/.claude/full-SRP/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

**Sesudah:**
```nginx
server {
    listen 80;
    server_name localhost;
    root E:/.claude/full-SRP/public/public_html;
    index index.php;

    # Deny access to src directory
    location ^~ /src/ {
        deny all;
        return 404;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 3: Copy Assets

```bash
# Copy assets folder
cp -r assets public_html/assets

# Copy PWA files
cp -r pwa public_html/pwa

# Copy icons
cp -r assets/icons public_html/assets/icons

# Copy manifest and service worker
cp manifest.json public_html/
cp service-worker.js public_html/
```

### Step 4: Setup Environment File

```bash
# Copy .env.example to .env
cp .env.example .env

# Edit .env with your credentials
nano .env  # or use your preferred editor
```

**Edit `.env`:**
```env
# Database Configuration
SRP_DB_HOST=127.0.0.1
SRP_DB_USER=root
SRP_DB_PASS=your_password_here
SRP_DB_NAME=srp
SRP_DB_PORT=3306
SRP_DB_SOCKET=

# Admin Credentials
SRP_ADMIN_USER=admin
SRP_ADMIN_PASSWORD_HASH=$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
SRP_ADMIN_PASSWORD=  # Optional: for development only

# API Configuration
SRP_API_KEY=your_secure_api_key_here

# Environment
SRP_ENV=production  # or development
```

**Generate password hash:**
```php
<?php
// generate_hash.php
$password = 'your_password';
echo password_hash($password, PASSWORD_DEFAULT);
```

Run:
```bash
php generate_hash.php
```

### Step 5: Update .htaccess

**File**: `public_html/.htaccess`

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect HTTP to HTTPS (production only)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

    # Prevent directory listing
    Options -Indexes

    # Deny access to sensitive files
    <FilesMatch "^\.">
        Order allow,deny
        Deny from all
    </FilesMatch>

    # Deny access to .env files
    <FilesMatch "\.env">
        Order allow,deny
        Deny from all
    </FilesMatch>

    # Handle Front Controller
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Enable Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache Control
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Step 6: Test Database Connection

Create test file:

**File**: `public_html/test_db.php`

```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';

use SRP\Config\Database;
use SRP\Config\Environment;

try {
    echo "Testing environment variables...\n";
    echo "DB Host: " . Environment::get('SRP_DB_HOST') . "\n";
    echo "DB Name: " . Environment::get('SRP_DB_NAME') . "\n";
    echo "DB User: " . Environment::get('SRP_DB_USER') . "\n";
    echo "\n";

    echo "Testing database connection...\n";
    $conn = Database::getConnection();
    echo "✓ Database connected successfully!\n";

    echo "\nTesting tables...\n";
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "✓ Table found: " . $row[0] . "\n";
    }

    echo "\n✓ All tests passed!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

Run:
```bash
php public_html/test_db.php
```

Expected output:
```
Testing environment variables...
DB Host: 127.0.0.1
DB Name: srp
DB User: root

Testing database connection...
✓ Database connected successfully!

Testing tables...
✓ Table found: settings
✓ Table found: logs

✓ All tests passed!
```

**Don't forget to delete `test_db.php` after testing!**

### Step 7: Restart Web Server

#### Apache
```bash
# Windows
httpd -k restart

# Linux
sudo systemctl restart apache2
```

#### Nginx
```bash
# Linux
sudo systemctl restart nginx
sudo systemctl restart php-fpm
```

### Step 8: Test All Endpoints

Create a test script:

**File**: `test_endpoints.sh` (Linux/Mac) or `test_endpoints.bat` (Windows)

```bash
#!/bin/bash

BASE_URL="http://localhost"

echo "Testing endpoints..."

# Test landing page
echo -n "Testing landing page... "
curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/landing.php"
echo ""

# Test login page
echo -n "Testing login page... "
curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/login.php"
echo ""

# Test dashboard (should redirect to login)
echo -n "Testing dashboard (should be 302)... "
curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/index.php"
echo ""

echo "✓ Basic endpoint tests completed!"
```

### Step 9: Manual Testing Checklist

- [ ] Landing page loads correctly (`/landing.php`)
- [ ] Login page loads correctly (`/login.php`)
- [ ] Login with valid credentials works
- [ ] Dashboard loads after login (`/index.php`)
- [ ] System toggle (ON/OFF) works
- [ ] Redirect URL configuration works
- [ ] Country filter configuration works
- [ ] Decision tester works
- [ ] Traffic logs display correctly
- [ ] Clear logs function works
- [ ] Logout works
- [ ] CSRF protection works (try submitting form without token)
- [ ] Rate limiting works (try 6+ failed login attempts)
- [ ] Decision API works (`POST /decision.php` with API key)

### Step 10: Compare Old vs New URLs

| Feature | Old URL | New URL | Status |
|---------|---------|---------|--------|
| Landing | `/landing.php` | `/landing.php` | ✓ Same |
| Login | `/login.php` | `/login.php` | ✓ Same |
| Dashboard | `/index.php` | `/index.php` | ✓ Same |
| Logout | `/logout.php` | `/logout.php` | ✓ Same |
| Data API | `/data.php` | `/data.php` | ✓ Same |
| Decision API | `/decision.php` | `/decision.php` | ✓ Same |
| Assets | `/assets/*` | `/assets/*` | ✓ Same |

**Catatan**: URLs tidak berubah! Hanya struktur internal yang berubah.

## Troubleshooting

### Issue 1: "Class not found" error

**Symptom:**
```
Fatal error: Class 'SRP\Controllers\DashboardController' not found
```

**Solution:**
1. Check namespace in class file matches directory structure
2. Check bootstrap.php is loaded correctly
3. Check file permissions

```bash
# Fix permissions (Linux)
chmod -R 755 src/
```

### Issue 2: Database connection error

**Symptom:**
```
DB init failed
```

**Solution:**
1. Check `.env` file exists and has correct credentials
2. Check MySQL service is running
3. Check database exists

```bash
# Check MySQL service
sudo systemctl status mysql

# Create database if not exists
mysql -u root -p
CREATE DATABASE IF NOT EXISTS srp;
```

### Issue 3: Session errors

**Symptom:**
```
Warning: session_start(): Failed to read session data
```

**Solution:**
1. Check session directory permissions

```bash
# Linux
sudo chmod 1733 /var/lib/php/sessions

# Check PHP session settings
php -i | grep session.save_path
```

### Issue 4: Assets not loading

**Symptom:**
CSS/JS files return 404

**Solution:**
1. Check assets copied to `public_html/assets/`
2. Check `.htaccess` allows static files
3. Check web server config

```bash
# Copy assets if missing
cp -r assets public_html/
```

### Issue 5: CSRF token errors

**Symptom:**
```
Invalid CSRF token
```

**Solution:**
1. Clear browser cookies
2. Refresh page to get new token
3. Check session is working

```php
// Debug: check session
var_dump($_SESSION);
```

## Rollback Plan

If migration fails, you can rollback:

### Quick Rollback

```bash
# Stop web server
sudo systemctl stop apache2  # or nginx

# Restore old files
rm -rf E:/.claude/full-SRP/public
mv E:/.claude/full-SRP/public_backup_YYYYMMDD E:/.claude/full-SRP/public

# Restore old web server config
# (restore from backup)

# Restore database
mysql -u root -p srp < srp_backup_YYYYMMDD.sql

# Start web server
sudo systemctl start apache2  # or nginx
```

## Post-Migration Tasks

### 1. Update Documentation

Update any internal documentation to reference new structure.

### 2. Update Deployment Scripts

If you have deployment scripts, update them to use new paths.

### 3. Monitor Error Logs

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php-fpm/error.log
```

### 4. Performance Testing

```bash
# Use Apache Bench
ab -n 1000 -c 10 http://localhost/index.php

# Or use siege
siege -c 10 -t 1M http://localhost/index.php
```

### 5. Security Audit

- [ ] Run security scanner (e.g., OWASP ZAP)
- [ ] Check file permissions
- [ ] Review environment variables
- [ ] Test CSRF protection
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention

### 6. Clean Up

After migration is successful and stable:

```bash
# Remove old backup (after 1 week)
rm -rf E:/.claude/full-SRP/public_backup_*

# Remove test files
rm public_html/test_db.php
rm test_endpoints.sh
```

## Performance Comparison

Test before and after migration:

### Metrics to Compare

| Metric | Old Structure | New Structure | Improvement |
|--------|--------------|---------------|-------------|
| Login page load time | ? ms | ? ms | ? % |
| Dashboard load time | ? ms | ? ms | ? % |
| API response time | ? ms | ? ms | ? % |
| Memory usage | ? MB | ? MB | ? % |
| Code maintainability | Low | High | ✓ |
| Test coverage | 0% | Ready for testing | ✓ |

## Next Steps

After successful migration:

1. **Add Unit Tests**: Write PHPUnit tests for models and controllers
2. **Add Integration Tests**: Test complete workflows
3. **Setup CI/CD**: Automate testing and deployment
4. **Performance Optimization**: Add caching, optimize queries
5. **Monitoring**: Setup error tracking (Sentry, Rollbar, etc.)
6. **Documentation**: Keep updating documentation

## Support

If you encounter issues during migration:

1. Check error logs
2. Review this migration guide
3. Check REFACTORING_DOCUMENTATION.md
4. Contact development team

---

**Migration Guide Version**: 1.0
**Last Updated**: 2025-11-17
**Compatible with**: SRP v2.0.0
