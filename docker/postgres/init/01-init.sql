-- PostgreSQL initialization script for Laravel Filament Admin Panel
-- This script runs when the PostgreSQL container starts for the first time

-- Ensure the database exists (usually created by POSTGRES_DB env var)
-- CREATE DATABASE IF NOT EXISTS admin_panel; -- PostgreSQL syntax is different

-- Grant all privileges to the user
GRANT ALL PRIVILEGES ON DATABASE admin_panel TO admin_panel_user;

-- Set default privileges for future objects
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO admin_panel_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO admin_panel_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO admin_panel_user;

-- Ensure the user can create schemas and extensions
GRANT CREATE ON SCHEMA public TO admin_panel_user;

-- Create commonly used extensions that Laravel might need
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Optional: Create a healthcheck function
CREATE OR REPLACE FUNCTION healthcheck() 
RETURNS TEXT AS $$
BEGIN
    RETURN 'PostgreSQL is running!';
END;
$$ LANGUAGE plpgsql; 