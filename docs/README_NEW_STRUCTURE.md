# SRP (Smart Redirect Platform) - Refactored Version 2.0

## 🎯 Gambaran Umum

SRP adalah platform redirect cerdas yang memungkinkan Anda untuk routing traffic berdasarkan:
- Device type (Mobile/Desktop/Tablet)
- Country (dengan whitelist/blacklist)
- VPN detection
- Auto mute/unmute cycling

Versi 2.0 ini telah di-refactor dari struktur monolitik menjadi **modern MVC web application** yang lebih terorganisir dan mudah di-maintain.

## 📁 Struktur Proyek

```
E:\.claude\full-SRP\public\
│
├── public_html/              ← Web server document root
│   ├── index.php            ← Dashboard (requires login)
│   ├── login.php            ← Login page
│   ├── logout.php           ← Logout
│   ├── landing.php          ← Public landing page
│   ├── data.php             ← Data API (GET/POST/DELETE)
│   ├── decision.php         ← Decision routing API
│   └── assets/              ← CSS, JS, icons
│
├── src/                      ← Application source code
│   ├── Controllers/         ← Business logic
│   ├── Models/              ← Data access layer
│   ├── Views/               ← UI templates
│   ├── Config/              ← Configuration
│   ├── Middleware/          ← Request handling
│   └── bootstrap.php        ← App initialization
│
├── .env                      ← Environment variables
├── .env.example             ← Environment template
└── [documentation files]
```

## 🚀 Quick Start

### 1. Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ or Nginx 1.18+
- Composer (optional)

### 2. Installation

```bash
# Clone atau copy project
cd E:\.claude\full-SRP\public\

# Copy environment file
cp .env.example .env

# Edit .env dengan credentials Anda
nano .env
```

### 3. Configure .env

```env
# Database
SRP_DB_HOST=127.0.0.1
SRP_DB_USER=root
SRP_DB_PASS=your_password
SRP_DB_NAME=srp
SRP_DB_PORT=3306

# Admin credentials
SRP_ADMIN_USER=admin
SRP_ADMIN_PASSWORD_HASH=$2y$10$...  # Generate dengan password_hash()

# API Key
SRP_API_KEY=your_secure_random_key
```

**Generate password hash:**
```php
<?php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

### 4. Configure Web Server

#### Apache
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

#### Nginx
```nginx
server {
    listen 80;
    server_name localhost;
    root E:/.claude/full-SRP/public/public_html;
    index index.php;

    location ^~ /src/ {
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

### 5. Start Application

```bash
# Restart web server
sudo systemctl restart apache2  # or nginx

# Visit in browser
http://localhost/login.php
```

## 📖 Usage

### Admin Dashboard

1. **Login**
   - Visit: `http://localhost/login.php`
   - Enter admin credentials from .env
   - Click "Sign In"

2. **Dashboard Features**
   - **System Toggle**: Turn routing ON/OFF
   - **Redirect URL**: Set target URL (must be HTTPS)
   - **Country Filter**: Configure whitelist/blacklist
   - **Decision Tester**: Test routing logic
   - **Traffic Logs**: View recent traffic

### API Endpoints

#### 1. Decision API

**Endpoint**: `POST /decision.php`

**Headers**:
```
X-API-Key: your_api_key_from_env
Content-Type: application/json
```

**Request Body**:
```json
{
  "click_id": "ABC123",
  "country_code": "US",
  "user_agent": "Mozilla/5.0...",
  "ip_address": "1.2.3.4",
  "user_lp": "campaign1"
}
```

**Response**:
```json
{
  "ok": true,
  "decision": "A",
  "target": "https://example.com"
}
```

**Decision Logic**:
- `A` = Redirect to configured URL (mobile, allowed country, no VPN, system ON)
- `B` = Fallback to safe page (desktop, VPN, blocked country, or system OFF)

#### 2. Data API (Authenticated)

**Get Config & Logs**:
```bash
GET /data.php
Headers: X-Requested-With: XMLHttpRequest
```

**Update Config**:
```bash
POST /data.php
Headers:
  X-CSRF-Token: {token from meta tag}
  Content-Type: application/json
Body:
{
  "system_on": true,
  "redirect_url": "https://example.com",
  "country_filter_mode": "whitelist",
  "country_filter_list": "US,GB,CA"
}
```

**Clear Logs**:
```bash
DELETE /data.php
Headers: X-CSRF-Token: {token}
```

## 🏗️ Architecture

### MVC Pattern

```
Request → Entry Point → Controller → Model → Database
                           ↓
                         View → Response
```

### Component Responsibilities

1. **Controllers** (`src/Controllers/`)
   - Handle HTTP requests
   - Validate input
   - Call models
   - Return views or JSON

2. **Models** (`src/Models/`)
   - Database operations
   - Business logic
   - Data validation

3. **Views** (`src/Views/`)
   - UI templates
   - Reusable components
   - Presentation logic

4. **Config** (`src/Config/`)
   - Database connection
   - Environment variables

5. **Middleware** (`src/Middleware/`)
   - Session management
   - Authentication
   - CSRF protection

## 🔒 Security Features

- **CSRF Protection**: All POST/DELETE requests require valid token
- **Rate Limiting**: Login attempts limited (5 per 15 minutes)
- **Input Validation**: All inputs sanitized and validated
- **SQL Injection Prevention**: Prepared statements used
- **XSS Prevention**: Output escaped with htmlspecialchars()
- **Session Security**: Secure cookie parameters
- **API Authentication**: API key required for decision endpoint

## 🧪 Testing

### Manual Testing

1. **Test Login**:
   ```
   Visit: /login.php
   - Try invalid credentials (should fail)
   - Try valid credentials (should succeed)
   - Try 6+ failed attempts (should rate limit)
   ```

2. **Test Dashboard**:
   ```
   Visit: /index.php (after login)
   - Toggle system ON/OFF
   - Change redirect URL
   - Test country filters
   - Run decision tester
   - Clear logs
   ```

3. **Test Decision API**:
   ```bash
   curl -X POST http://localhost/decision.php \
     -H "X-API-Key: your_api_key" \
     -H "Content-Type: application/json" \
     -d '{
       "click_id": "test123",
       "country_code": "US",
       "user_agent": "mobile",
       "ip_address": "1.2.3.4"
     }'
   ```

### Unit Testing (Future)

Structure ready for PHPUnit:

```php
// tests/Models/SettingsTest.php
class SettingsTest extends TestCase {
    public function testGetSettings() {
        $settings = Settings::get();
        $this->assertArrayHasKey('system_on', $settings);
    }
}
```

## 📊 Monitoring

### Error Logs

**Apache**:
```bash
tail -f /var/log/apache2/error.log
```

**Nginx**:
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/php-fpm/error.log
```

**PHP**:
```bash
tail -f /var/log/php/error.log
```

### Traffic Logs

View in dashboard or query database:
```sql
SELECT * FROM logs
ORDER BY ts DESC
LIMIT 50;
```

### Cleanup Old Logs

Automatic cleanup (via cron):
```php
<?php
require __DIR__ . '/src/bootstrap.php';
use SRP\Models\TrafficLog;

$deleted = TrafficLog::autoCleanup(7); // Keep last 7 days
echo "Deleted $deleted old log entries\n";
```

Add to crontab:
```cron
0 2 * * * php /path/to/public/cleanup_logs.php
```

## 🔧 Customization

### Add New Controller

```php
<?php
// src/Controllers/ReportController.php
namespace SRP\Controllers;

class ReportController
{
    public static function index(): void
    {
        // Your logic here
        require __DIR__ . '/../Views/report.view.php';
    }
}
```

Entry point:
```php
<?php
// public_html/report.php
require_once __DIR__ . '/../src/bootstrap.php';
use SRP\Controllers\ReportController;
ReportController::index();
```

### Add New Model

```php
<?php
// src/Models/Report.php
namespace SRP\Models;

use SRP\Config\Database;

class Report
{
    public static function generate(): array
    {
        $conn = Database::getConnection();
        // Your logic here
        return [];
    }
}
```

### Add New View

```php
<?php
// src/Views/report.view.php
$pageTitle = 'Reports';
require __DIR__ . '/components/header.php';
?>

<main>
    <!-- Your HTML here -->
</main>

<?php require __DIR__ . '/components/footer.php'; ?>
```

## 🐛 Troubleshooting

### Issue: Class not found

**Solution**:
```bash
# Check bootstrap.php is loaded
# Check namespace matches directory structure
# Check file permissions
chmod -R 755 src/
```

### Issue: Database connection error

**Solution**:
```bash
# Check .env file
# Check MySQL is running
sudo systemctl status mysql

# Check database exists
mysql -u root -p
SHOW DATABASES;
```

### Issue: 404 on all pages

**Solution**:
```bash
# Check document root points to public_html
# Check .htaccess exists
# Check mod_rewrite enabled (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Issue: CSRF token error

**Solution**:
```bash
# Clear browser cookies
# Refresh page
# Check session is working
```

## 📚 Documentation

- **REFACTORING_DOCUMENTATION.md** - Complete technical documentation
- **STRUCTURE_DIAGRAM.md** - Visual architecture diagrams
- **MIGRATION_GUIDE.md** - Migration from old structure
- **REFACTORING_SUMMARY.md** - Quick overview of changes

## 🤝 Contributing

### Code Style

- Follow PSR-12 coding standard
- Use type hints
- Document public methods
- Keep functions small and focused

### Adding Features

1. Create controller in `src/Controllers/`
2. Create model if needed in `src/Models/`
3. Create view in `src/Views/`
4. Create entry point in `public_html/`
5. Update documentation

### Reporting Bugs

Include:
- PHP version
- Web server (Apache/Nginx)
- Error messages
- Steps to reproduce

## 📄 License

[Your License Here]

## 👥 Authors

- Original Code: [Your Team]
- Refactored by: Claude AI (2025-11-17)

## 🙏 Acknowledgments

- Tailwind CSS for UI
- Alpine.js for interactivity
- Mobile Detect library for device detection

---

**Version**: 2.0.0
**Last Updated**: 2025-11-17
**Status**: ✅ Production Ready
