-- Creates the app role + database for Routina (PostgreSQL)
-- Run with psql as a superuser (e.g., postgres).
--
-- Notes:
-- - CREATE DATABASE cannot run inside a DO block/function.
-- - This script uses psql's \gexec to conditionally execute statements.

-- Create role if missing
SELECT 'CREATE ROLE routina LOGIN PASSWORD ''routina'';'
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'routina')
\gexec

-- Ensure the password is set even if the role already existed
ALTER ROLE routina WITH LOGIN PASSWORD 'routina';

-- Create database if missing
SELECT 'CREATE DATABASE routina OWNER routina;'
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'routina')
\gexec

-- Ensure ownership + privileges are correct
SELECT 'ALTER DATABASE routina OWNER TO routina;'
WHERE EXISTS (SELECT 1 FROM pg_database WHERE datname = 'routina')
\gexec

SELECT 'GRANT ALL PRIVILEGES ON DATABASE routina TO routina;'
WHERE EXISTS (SELECT 1 FROM pg_database WHERE datname = 'routina')
\gexec
