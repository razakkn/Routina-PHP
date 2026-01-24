# Routina PHP Deployment Instructions

## Local Development (PostgreSQL)
1. Ensure PostgreSQL is running locally.
2. Ensure PHP has `pdo_pgsql` enabled.
3. Initialize schema:
    - `php setup_database.php`
4. Run the dev server:
    - `php -S 127.0.0.1:8080 -t public`

Shortcut:
- `powershell -ExecutionPolicy Bypass -File scripts/run_local.ps1`

## 1. Move the Project
The new PHP application is located in the `routina_php` folder. You can move this folder entirely to a new location on your computer to separate it from the old .NET project.

## 2. Shared Hosting Setup
1.  **Upload**: Upload the contents of this folder to your hosting space.
2.  **Public Folder**: 
    *   Ideally, point your web server's "Document Root" to the `public/` folder.
    *   If you cannot change the document root, you can move the contents of `public/` to your `public_html` or `www` folder, and adjust the `require` paths in `index.php` to point back to `../src`.
3.  **Database**:
    *   Open `config/config.php`.
    *   Change `db_connection` to `mysql` or `pgsql`.
    *   Fill in your `db_host`, `db_name`, `db_user`, and `db_pass`.
    *   For PostgreSQL, ensure the PHP extension `pdo_pgsql` is enabled on your server.
4.  **PHP Version**: Ensure your hosting panel is set to use **PHP 8.2** or **8.3**.

## 3. Directory Structure
*   `public/`: Contains `index.php` and assets (CSS/JS). This is the only folder that needs to be accessible from the web.
*   `src/`: Contains your PHP Classes (Controllers, Models).
*   `views/`: Contains your HTML templates.
*   `config/`: Configuration files.

## 4. Next Steps
This is a **structural port**. The following logic still needs to be manually migrated from C# to PHP:
*   [ ] **Authentication**: Implement a login system (using `src/Services/AuthService.php` - *to be created*).
*   [ ] **Database Tables**: Create MySQL tables matching your previous Entity Framework migrations.
*   [ ] **Remaining Controllers**: Port logic for Vehicle, Journal, Vacation, etc.
