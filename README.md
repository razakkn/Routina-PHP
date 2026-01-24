# Routina (PHP)

Small PHP app with a lightweight MVC-ish structure.

## Local setup

1. Create your config:
   - Copy `config/config.example.php` to `config/config.php`
   - Set DB connection values.

2. (PostgreSQL) Create DB + user and run setup:
   - `php setup_database.php`

3. Run the app:
   - `php -S 127.0.0.1:8080 -t public`
   - Open `http://127.0.0.1:8080/`

## Notes

- `config/config.php` is intentionally not tracked by git.
- Runtime/cache files are ignored via `.gitignore`.
