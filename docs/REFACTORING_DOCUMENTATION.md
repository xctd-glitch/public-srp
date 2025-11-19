# Dokumentasi Refactoring - SRP Web Application

## Gambaran Umum

Proyek SRP (Smart Redirect Platform) telah di-refactor dari struktur monolitik menjadi struktur web application yang terorganisir dengan baik mengikuti pola MVC (Model-View-Controller) dan prinsip separation of concerns.

## Struktur Direktori Baru

```
E:\.claude\full-SRP\public\
│
├── public_html/                    # Document root (public facing)
│   ├── index.php                   # Entry point untuk dashboard
│   ├── login.php                   # Entry point untuk halaman login
│   ├── logout.php                  # Entry point untuk logout
│   ├── landing.php                 # Entry point untuk landing page
│   ├── data.php                    # Entry point untuk API data
│   ├── decision.php                # Entry point untuk decision API
│   ├── assets/
│   │   ├── css/                    # CSS files
│   │   ├── js/                     # JavaScript files
│   │   └── icons/                  # Icon files
│   └── .htaccess                   # Apache configuration
│
├── src/                            # Application source code
│   ├── Controllers/                # Controllers (Business Logic)
│   │   ├── AuthController.php      # Handling login/logout
│   │   ├── DashboardController.php # Handling dashboard & landing
│   │   ├── ApiController.php       # Handling data API
│   │   └── DecisionController.php  # Handling decision logic
│   │
│   ├── Models/                     # Models (Data Layer)
│   │   ├── Settings.php            # Settings management
│   │   ├── TrafficLog.php          # Traffic log management
│   │   └── Validator.php           # Validation utilities
│   │
│   ├── Views/                      # Views (Presentation Layer)
│   │   ├── components/             # Reusable view components
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   ├── toast.php
│   │   │   └── dashboard-content.php
│   │   ├── dashboard.view.php      # Dashboard view
│   │   ├── login.view.php          # Login view
│   │   └── landing.view.php        # Landing page view
│   │
│   ├── Config/                     # Configuration
│   │   ├── Database.php            # Database connection & schema
│   │   └── Environment.php         # Environment variable loader
│   │
│   ├── Middleware/                 # Middleware components
│   │   └── Session.php             # Session management
│   │
│   └── bootstrap.php               # Application bootstrap file
│
├── .env                            # Environment variables
├── .env.example                    # Environment variables template
└── README.md                       # Project documentation
```

## Komponen Utama

### 1. **Entry Points** (`public_html/`)

File-file di direktori ini adalah entry point yang diakses langsung oleh user melalui browser:

- `index.php` → Dashboard utama
- `login.php` → Halaman login
- `logout.php` → Proses logout
- `landing.php` → Landing page publik
- `data.php` → API untuk data dashboard (GET, POST, DELETE)
- `decision.php` → API untuk decision routing

Semua file ini sangat sederhana, hanya meload bootstrap dan memanggil controller yang sesuai.

**Contoh:**
```php
<?php
require_once __DIR__ . '/../src/bootstrap.php';
use SRP\Controllers\DashboardController;
DashboardController::index();
```

### 2. **Controllers** (`src/Controllers/`)

Controllers menangani business logic dan request handling:

#### `AuthController.php`
- `login()` - Menampilkan form login dan proses autentikasi
- `logout()` - Proses logout dan clear session
- `handleLoginAttempt()` - Rate limiting & validasi kredensial

#### `DashboardController.php`
- `index()` - Menampilkan dashboard utama (requires auth)
- `landing()` - Menampilkan landing page publik

#### `ApiController.php`
- `handleDataRequest()` - Handle GET/POST/DELETE untuk data API
- `getData()` - Return config & logs dalam format JSON
- `postData()` - Update config settings
- `deleteLogs()` - Clear traffic logs

#### `DecisionController.php`
- `handleDecision()` - Logic untuk decision routing
- `detectDevice()` - Deteksi device type (WAP/WEB/TABLET/BOT)
- `checkVpn()` - Check VPN/proxy via external API

### 3. **Models** (`src/Models/`)

Models menangani data layer dan business logic terkait data:

#### `Settings.php`
- `get()` - Ambil settings saat ini
- `update()` - Update settings dengan validasi
- `getCountryFilter()` - Get country filter configuration
- `validateUrl()` - Validasi redirect URL (harus HTTPS)

#### `TrafficLog.php`
- `create()` - Insert log baru ke database
- `getAll()` - Ambil semua log dengan limit
- `clearAll()` - Hapus semua log
- `autoCleanup()` - Auto cleanup berdasarkan retention period
- `getStats()` - Statistik log (total, oldest, newest)

#### `Validator.php`
- `isValidIp()` - Validasi IP address
- `sanitizeString()` - Sanitize input string
- `isValidCountryCode()` - Validasi ISO country code
- `isCountryAllowed()` - Check apakah country diperbolehkan

### 4. **Views** (`src/Views/`)

Views menangani presentation layer:

#### Components (`components/`)
- `header.php` - HTML head & opening body tag
- `footer.php` - Footer & closing body tag
- `toast.php` - Toast notification component
- `dashboard-content.php` - Dashboard main content

#### Full Views
- `dashboard.view.php` - Complete dashboard page
- `login.view.php` - Login page
- `landing.view.php` - Landing page

### 5. **Config** (`src/Config/`)

#### `Database.php`
- Singleton database connection
- Schema initialization (CREATE TABLE IF NOT EXISTS)
- MySQLi connection dengan error handling

#### `Environment.php`
- Load environment variables dari .env file
- Support untuk multiple .env files (.env, .env.{environment}, custom)
- Helper method `get()` untuk akses environment variables

### 6. **Middleware** (`src/Middleware/`)

#### `Session.php`
- `start()` - Start session dengan secure cookie params
- `requireAuth()` - Middleware untuk require authentication
- `getCsrfToken()` - Generate/get CSRF token
- `validateCsrfToken()` - Validate CSRF token

### 7. **Bootstrap** (`src/bootstrap.php`)

File ini di-load pertama kali oleh semua entry points:
- Register PSR-4 autoloader untuk namespace `SRP\`
- Load environment variables via `Environment::load()`

## Perubahan dari Struktur Lama

### Struktur Lama:
```
public/
├── index.php (900+ lines, campur logic & view)
├── login.php (300+ lines, campur logic & view)
├── data.php (185 lines, mixed concerns)
├── decision.php (227 lines, mixed concerns)
├── _bootstrap.php (661 lines, semua function dalam 1 file)
└── assets/
```

### Struktur Baru:
- **Separation of Concerns**: Logic, Data, dan View terpisah
- **MVC Pattern**: Controllers, Models, Views
- **Reusability**: Components dapat digunakan ulang
- **Maintainability**: Lebih mudah maintain & debug
- **Testability**: Lebih mudah untuk unit testing
- **Scalability**: Mudah menambah fitur baru

## Cara Migrasi

### 1. Update Web Server Configuration

Ubah document root dari:
```
DocumentRoot "E:\.claude\full-SRP\public"
```

Menjadi:
```
DocumentRoot "E:\.claude\full-SRP\public\public_html"
```

### 2. Update .htaccess (jika perlu)

File `.htaccess` sudah ada di public_html. Pastikan mod_rewrite enabled.

### 3. Environment Variables

Copy `.env.example` menjadi `.env` dan sesuaikan:
```bash
cp .env.example .env
```

Edit `.env`:
```env
SRP_DB_HOST=127.0.0.1
SRP_DB_USER=root
SRP_DB_PASS=your_password
SRP_DB_NAME=srp
SRP_DB_PORT=3306

SRP_ADMIN_USER=admin
SRP_ADMIN_PASSWORD_HASH=$2y$10$...
SRP_API_KEY=your_api_key_here
```

### 4. Testing

Test semua endpoints:
- http://localhost/index.php (Dashboard)
- http://localhost/login.php (Login)
- http://localhost/landing.php (Landing)
- http://localhost/data.php (API Data)
- http://localhost/decision.php (Decision API)

## Security Improvements

1. **CSRF Protection**: Semua POST/DELETE request require valid CSRF token
2. **Rate Limiting**: Login attempts dibatasi (5 attempts per 15 minutes)
3. **Input Validation**: Semua input di-sanitize dan di-validate
4. **SQL Injection Prevention**: Menggunakan prepared statements
5. **XSS Prevention**: htmlspecialchars() untuk semua output
6. **Session Security**: Secure cookie params (httponly, samesite, secure)

## Performance Improvements

1. **Autoloading**: PSR-4 autoloader, load classes on demand
2. **Connection Pooling**: Singleton database connection
3. **Caching**: Proper cache headers untuk API
4. **Code Organization**: Lebih mudah untuk optimize specific components

## Keuntungan Struktur Baru

1. **Maintainability** ⭐⭐⭐⭐⭐
   - Code terorganisir dengan baik
   - Mudah mencari dan fix bugs
   - Clear separation of concerns

2. **Scalability** ⭐⭐⭐⭐⭐
   - Mudah menambah fitur baru
   - Mudah menambah controller/model/view baru
   - Mudah integrate dengan framework (Laravel, Symfony, etc)

3. **Testability** ⭐⭐⭐⭐⭐
   - Mudah untuk unit testing
   - Mudah untuk mock dependencies
   - Clear boundaries antar components

4. **Reusability** ⭐⭐⭐⭐⭐
   - View components dapat digunakan ulang
   - Models dapat digunakan di multiple controllers
   - Validators dapat digunakan di berbagai tempat

5. **Security** ⭐⭐⭐⭐⭐
   - Centralized validation
   - Consistent CSRF protection
   - Better session management

## Next Steps (Opsional)

1. **Add Unit Tests**: PHPUnit untuk testing models & controllers
2. **Add API Documentation**: Swagger/OpenAPI untuk API endpoints
3. **Add Logging**: Monolog untuk application logging
4. **Add Caching**: Redis/Memcached untuk caching
5. **Migrate to Framework**: Laravel/Symfony untuk full-stack framework
6. **Add Frontend Build**: Webpack/Vite untuk asset compilation

## Troubleshooting

### Autoloader tidak bekerja
- Pastikan namespace `SRP\` sesuai dengan struktur folder
- Pastikan file class menggunakan `namespace SRP\...;`
- Pastikan bootstrap.php di-load dengan benar

### Database connection error
- Cek credentials di .env
- Pastikan MySQL service running
- Cek Database.php untuk connection config

### Session issues
- Cek session directory writable
- Cek php.ini session configuration
- Clear browser cookies

### CSRF token invalid
- Refresh page untuk generate token baru
- Cek session tidak expired
- Pastikan form include csrf_token hidden input

## Kontak

Jika ada pertanyaan atau issue, silakan buat issue di repository atau hubungi development team.

---

**Refactoring completed**: 2025-11-17
**Structure version**: 2.0.0
