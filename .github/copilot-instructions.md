# Copilot instructions for Routina (PHP)

## Big picture
- Lightweight “MVC-ish” PHP app (no framework / no Composer). HTTP entrypoint + router is in [public/index.php](public/index.php).
- Backend code lives in [src/](src/) under the `Routina\` namespace; file paths mirror namespaces (custom autoloader in [public/index.php](public/index.php)).
- Persistence uses `PDO` via [src/Config/Database.php](src/Config/Database.php), configured by [config/config.php](config/config.php) (untracked; copy from [config/config.example.php](config/config.example.php)).
- Views are plain PHP templates in [views/](views/), typically wrapped by [views/layouts/main.php](views/layouts/main.php). Static assets live in [public/css/](public/css/) and [public/js/](public/js/).

## Local dev workflows (Windows-friendly)
- Create config: copy [config/config.example.php](config/config.example.php) → `config/config.php` and set DB values.
- Initialize schema: `php setup_database.php` (prints progress; creates/patches tables).
- Run: `php -S 127.0.0.1:8080 -t public` or `powershell -ExecutionPolicy Bypass -File scripts/run_local.ps1`.
- Postgres bootstrap helper: [scripts/init_postgres_routina.sql](scripts/init_postgres_routina.sql).

## Routing + controller conventions
- Routing is mostly explicit `if ($requestUri === ...) { ... }` blocks in [public/index.php](public/index.php), plus the `$appRoutes` map for module root pages.
- To add a new page:
  - Add a route in [public/index.php](public/index.php) (or extend `$appRoutes`).
  - Implement the handler in a controller in [src/Controllers/](src/Controllers/) (common shape: `index()` for GET pages, and a named action for POST).
  - Render with the global `view('path/to/view', ['key' => $val])` helper defined in [public/index.php](public/index.php).
- Auth is session-based: logged-in user is `$_SESSION['user_id']`; admin gating uses `admin_emails` from config and `require_admin()` in [public/index.php](public/index.php).

## Forms, CSRF, and responses
- CSRF is enforced globally for all POSTs by `csrf_verify()` in [public/index.php](public/index.php). Use `<?= csrf_field() ?>` (hidden input name `_csrf`) in every POST form (see [views/layouts/main.php](views/layouts/main.php)).
- For JSON endpoints, use the global `json_response(...)` helper or `abort(...)` which returns JSON when `Accept: application/json`.

## Data access patterns (Models/Services)
- Models in [src/Models/](src/Models/) are thin, static CRUD wrappers around `PDO` (example: [src/Models/Vacation.php](src/Models/Vacation.php)). Prefer prepared statements and pass `user_id` for multi-tenant scoping.
- “Services” in [src/Services/](src/Services/) hold cross-cutting/business logic (examples: [src/Services/AuthService.php](src/Services/AuthService.php), [src/Services/LayoutService.php](src/Services/LayoutService.php)).
- The schema is managed by code, not migrations: [setup_database.php](setup_database.php) uses `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` and some services (e.g. `AuthService`) defensively tolerate schema drift across MySQL/SQLite/Postgres.

## External integrations to be aware of
- Google OAuth routes exist under `/auth/google` and `/auth/google/callback` (config keys referenced in [DEPLOY_INSTRUCTIONS.md](DEPLOY_INSTRUCTIONS.md)).
- Vehicle model lookup uses the public vPIC API at `/api/vehicle/models` (implemented in [public/index.php](public/index.php)).

## Repo-specific gotchas
- `config/config.php` is intentionally not committed; don’t add secrets to git. Also treat deployment scripts as sensitive (see [scripts/ftp_upload.ps1](scripts/ftp_upload.ps1)).
- [src/Config/Database.php](src/Config/Database.php) directly requires `config/config.php`; the app’s `app_config()` falls back to the example config, but DB setup won’t.
