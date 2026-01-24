# Routina PHP - Deployment Guide

## Local Development

### PostgreSQL (Default)
```bash
# Ensure PostgreSQL is running
php setup_database.php
php -S 127.0.0.1:8080 -t public
```

### Windows PowerShell Shortcut
```powershell
powershell -ExecutionPolicy Bypass -File scripts/run_local.ps1
```

---

## Shared Hosting Deployment (cPanel / InfinityFree / ezyro)

### Prerequisites
- cPanel access with File Manager
- MySQL database access
- PHP 7.4+ (PHP 8.x recommended)
- FTP client (FileZilla) or cPanel File Manager

---

## Step 1: Create MySQL Database in cPanel

1. **Login to cPanel** (e.g., `yourdomain.com/cpanel` or hosting panel URL)

2. **Go to MySQL Databases**
   - Under "Databases" section, click **MySQL® Databases**

3. **Create a new database** (if not already created)
   - Your database: `ezyro_40939469_routina`

4. **Create a database user** (if needed)
   - Username: `ezyro_40939469`
   - Password: Your password

5. **Add user to database**
   - Select the user and database
   - Grant **ALL PRIVILEGES**
   - Click "Make Changes"

6. **Note the MySQL hostname**
   - Usually shown in cPanel (e.g., `sql300.ezyro.com` or `localhost`)

---

## Step 2: Prepare Files for Upload

### Option A: Using Git (if hosting supports SSH)
```bash
cd public_html
git clone https://github.com/razakkn/Routina-PHP.git .
```

### Option B: Manual Upload via FTP/File Manager

1. **Download your project** as a ZIP from GitHub or use local files

2. **Upload the following folders/files to `public_html`:**
   ```
   public_html/
   ├── config/
   │   └── config.php          (production version)
   ├── public/
   │   ├── index.php
   │   ├── css/
   │   └── js/
   ├── src/
   │   ├── Config/
   │   ├── Controllers/
   │   ├── Models/
   │   └── Services/
   ├── views/
   ├── storage/
   ├── setup_database.php
   └── .htaccess               (copy from public/.htaccess)
   ```

---

## Step 3: Configure for Production

### Update config/config.php

Edit `config/config.php` on the server:

```php
<?php
return [
    'app_name' => 'Routina',
    'app_url' => 'https://your-subdomain.ezyro.com',

    'admin_emails' => ['your-email@example.com'],
    
    // MySQL Configuration
    'db_connection' => 'mysql',
    'db_host' => 'sql300.ezyro.com',      // Check cPanel for actual host
    'db_port' => 3306,
    'db_name' => 'ezyro_40939469_routina',
    'db_user' => 'ezyro_40939469',
    'db_pass' => 'P@ssph6ase',

    // Google OAuth (update redirect URI)
    'google_client_id' => 'your-google-client-id',
    'google_client_secret' => 'your-google-client-secret',
    'google_redirect_uri' => 'https://your-subdomain.ezyro.com/auth/google/callback',

    // Email
    'mail_from' => 'noreply@your-domain.com',
    'mail_from_name' => 'Routina',
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
];
```

---

## Step 4: Adjust File Structure for Shared Hosting

Shared hosting typically serves from `public_html`. You need to make the `public/` folder your document root.

### Method 1: Move public/* contents to root (Easiest)

1. Move all files from `public/` to `public_html/` root
2. Update `public_html/index.php` paths:

**Edit index.php** - change these lines at the top:
```php
// Change FROM:
require_once __DIR__ . '/../src/Config/Database.php';
$config = require __DIR__ . '/../config/config.php';

// Change TO:
require_once __DIR__ . '/src/Config/Database.php';
$config = require __DIR__ . '/config/config.php';
```

And update view paths:
```php
// Change FROM:
$viewPath = __DIR__ . '/../views';

// Change TO:
$viewPath = __DIR__ . '/views';
```

### Method 2: Use .htaccess redirect (if your host allows)

Create `.htaccess` in `public_html/` root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Step 5: Run Database Setup

### Via Browser (Easiest)
1. Temporarily rename `setup_database.php` to something accessible (e.g., `_init_db_xyz123.php`)
2. Visit: `https://your-domain.com/_init_db_xyz123.php`
3. You should see output like:
   ```
   Using MySQL
   Creating users table...
   Creating password_resets table...
   ...
   MySQL Database setup complete.
   ```
4. **Delete or rename** the setup file immediately after!

### Via SSH (if available)
```bash
cd public_html
php setup_database.php
```

---

## Step 6: Set Permissions

Via cPanel File Manager:
- Right-click `storage/` folder → Change Permissions → 755 or 777

Or via SSH:
```bash
chmod 755 public_html
chmod 644 public_html/*.php
chmod 755 public_html/storage
chmod -R 755 public_html/storage/
```

---

## Step 7: Update Google OAuth Redirect URI

1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Edit your OAuth 2.0 Client ID
3. Add your production redirect URI:
   ```
   https://your-subdomain.ezyro.com/auth/google/callback
   ```

---

## Troubleshooting

### Error: "Database connection failed"
- Check MySQL hostname (might be `localhost` or specific server like `sql300.ezyro.com`)
- Verify username/password
- Ensure user has privileges on the database

### Error: 500 Internal Server Error
- Check error logs in cPanel → Errors
- Common causes:
  - PHP version mismatch
  - Missing PHP extensions (PDO, pdo_mysql)
  - File permission issues

### Error: "Page not found" for routes
- Ensure `.htaccess` is uploaded and mod_rewrite is enabled
- Some hosts require you to enable "Apache Handlers" in cPanel

### Error: Google OAuth redirect mismatch
- The redirect URI must EXACTLY match what's in Google Console
- Include the full path: `https://domain.com/auth/google/callback`
- Check for http vs https mismatch

---

## Security Checklist

- [ ] Delete `setup_database.php` after running
- [ ] Ensure `config.php` is not accessible via browser
- [ ] Use HTTPS (free SSL from cPanel → SSL/TLS or Let's Encrypt)
- [ ] Set proper file permissions
- [ ] Update Google OAuth callback URLs
- [ ] Remove demo user or change password

---

## Quick Reference

| Item | Value |
|------|-------|
| MySQL Host | `sql300.ezyro.com` (check cPanel) |
| Database | `ezyro_40939469_routina` |
| Username | `ezyro_40939469` |
| DB Connection | `mysql` |
| PHP Version | 7.4+ / 8.x |

---

## Directory Structure

```
public_html/
├── config/           # Configuration files (keep secure!)
├── public/           # Web-accessible files (index.php, css/, js/)
├── src/              # PHP Classes (Controllers, Models, Services)
├── views/            # HTML/PHP templates
├── storage/          # Uploads, logs (writable)
└── .htaccess         # URL rewriting
```
