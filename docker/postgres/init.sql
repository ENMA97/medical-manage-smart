-- Medical ERP - PostgreSQL Initialization
-- This script runs on first container start only

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create application schema
CREATE SCHEMA IF NOT EXISTS public;

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE medical_erp TO erp_user;
GRANT ALL PRIVILEGES ON SCHEMA public TO erp_user;
