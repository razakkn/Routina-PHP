# Changelog

## [Unreleased]

### Security
- Hardened HTTPS redirect to use configured app host and removed request URI to avoid open redirect flags.
- Switched vPIC cache key hashing to SHA-256.
- Updated MFA TOTP to default to SHA-256 while accepting legacy SHA-1 for verification.
- Restricted avatar file deletion to safe paths under uploads to prevent path traversal.
- Masked database connection errors from end users while logging server-side.

### Maintenance
- Removed setup/migration/test scripts after one-time use (setup_database.php, setup_db_web.php, migrate scripts, test_db.php).
- Removed prod config templates with placeholder passwords from the repo.

### Notes
- HTTPS redirect no longer preserves the original request path. If you need deep links to survive HTTP→HTTPS, reintroduce with strict path validation.
- Deleted setup/migration scripts require alternate deployment/runbook steps.