# FixMyWindow — Hostinger Deployment Guide

> Tested and verified on Hostinger shared hosting (in-mum-web1988)
> Server: `u162468524@<server-ip>` | Domain: `fixmywindow.co`

---

## Server Folder Structure

```
/home/u162468524/
├── composer.phar                          ← Composer binary
└── domains/
    └── fixmywindow.co/
        └── public_html/
            ├── index.html                 ← Frontend (React/Vite)
            ├── assets/                    ← Frontend assets
            ├── .htaccess                  ← Root rewrite rules (frontend + API routing)
            └── api/                       ← Laravel backend (testing only — move outside public_html in production)
                ├── index.php              ← Laravel entry point
                ├── .htaccess              ← Laravel subfolder rewrite rules
                ├── app/
                ├── bootstrap/
                ├── config/
                ├── database/
                ├── routes/
                ├── storage/
                ├── vendor/
                └── ...
```

> **Production note:** The full Laravel app inside `public_html/api/` exposes source files.
> For production, move the app to `/home/u162468524/FixMyWindowBackend/` and keep only
> `index.php` and `.htaccess` inside `public_html/api/`.

---

## API Base URL

```
https://fixmywindow.co/api/v1/
```

---

## Key Configuration Changes

### 1. `bootstrap/app.php` — Remove API prefix

Since Laravel is served from the `/api/` subfolder, the automatic `/api` route prefix
causes a double-prefix mismatch. Fix by setting `apiPrefix: ''`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    apiPrefix: '',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### 2. `public_html/.htaccess` — Root routing (frontend + API)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    RewriteCond %{HTTP:Authorization} ^(.+)$
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]

    RewriteCond %{REQUEST_URI} ^/api/
    RewriteRule ^ - [L]

    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    RewriteRule . /index.html [L]
</IfModule>
```

### 3. `public_html/api/.htaccess` — Laravel subfolder rewrite

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On
    RewriteBase /api/

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 4. `public_html/api/index.php` — Laravel entry point

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

(require_once __DIR__.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

---

## Deployment Steps

### 1. PHP Configuration
```
hPanel → Websites → Manage → Advanced → PHP Configuration
→ Set PHP version to 8.2+
→ Enable extensions: sodium (if available)
```

### 2. Upload files
Upload all Laravel files **except** `vendor/`, `.env`, `node_modules/` to `public_html/api/` via Hostinger File Manager or SFTP.

### 3. Install Composer dependencies
```bash
cd ~/domains/fixmywindow.co/public_html/api
php ~/composer.phar install --optimize-autoloader --no-dev

# If ext-sodium is missing (shared hosting limitation):
php ~/composer.phar install --optimize-autoloader --no-dev --ignore-platform-req=ext-sodium
```

### 4. Create `.env`
```bash
cp .env.production .env
```

Key `.env` values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fixmywindow.co/api

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=database
JWT_TTL=1440
JWT_REFRESH_TTL=43200
```

### 5. Generate keys
```bash
php artisan key:generate
php artisan jwt:secret
```

### 6. Set permissions
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 7. Run migrations
```bash
php artisan migrate --force
# For a clean slate:
php artisan migrate:fresh --force
```

### 8. Cache for production
```bash
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

### 9. Queue cron job
In `hPanel → Advanced → Cron Jobs`:
```
* * * * * /usr/local/bin/php /home/u162468524/domains/fixmywindow.co/public_html/api/artisan schedule:run >> /dev/null 2>&1
```

In `routes/console.php`:
```php
Schedule::command('queue:work --stop-when-empty')->everyMinute();
```

---

## Re-deploy Checklist

After pushing new code:

```bash
cd ~/domains/fixmywindow.co/public_html/api
php ~/composer.phar install --optimize-autoloader --no-dev --ignore-platform-req=ext-sodium
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| `{"message":"Resource not found"}` on all routes | Check `apiPrefix: ''` is set in `bootstrap/app.php` |
| JWT not working | Verify both `.htaccess` files have Authorization header rewrite rules |
| 500 on all pages | Set `APP_DEBUG=true` temporarily, check error logs |
| Composer fails (ext-sodium) | Add `--ignore-platform-req=ext-sodium` flag |
| Queue jobs not running | Verify cron path — run `which php` on SSH to confirm PHP binary path |
| DB connection refused | Confirm `DB_HOST=127.0.0.1` (not `localhost`) |
| Changes not reflecting | Run `php artisan config:cache && php artisan route:cache` |
