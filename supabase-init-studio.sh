#!/bin/bash

# Initialize Supabase Studio with direct database connection
# This creates the necessary database schema for Studio to work

echo "🔧 Initializing Supabase database for Studio..."

# Wait for database to be ready
echo "Waiting for database to be ready..."
until docker exec ddev-test-app-supabase-db pg_isready -U postgres; do
  echo "Database not ready, waiting..."
  sleep 2
done

echo "✅ Database is ready!"

# Create necessary extensions and schemas
docker exec ddev-test-app-supabase-db psql -U postgres -d postgres << 'EOF'
-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Create schemas
CREATE SCHEMA IF NOT EXISTS auth;
CREATE SCHEMA IF NOT EXISTS storage;
CREATE SCHEMA IF NOT EXISTS _realtime;
CREATE SCHEMA IF NOT EXISTS extensions;
CREATE SCHEMA IF NOT EXISTS information_schema;

-- Create basic tables for storage
CREATE TABLE IF NOT EXISTS storage.buckets (
    id text PRIMARY KEY,
    name text NOT NULL,
    owner uuid,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now(),
    public boolean DEFAULT false,
    file_size_limit bigint,
    allowed_mime_types text[]
);

-- Create storage objects table
CREATE TABLE IF NOT EXISTS storage.objects (
    id uuid DEFAULT uuid_generate_v4() PRIMARY KEY,
    bucket_id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now(),
    last_accessed_at timestamptz,
    file_size bigint,
    etag text,
    metadata jsonb
);

-- Create auth users table
CREATE TABLE IF NOT EXISTS auth.users (
    id uuid PRIMARY KEY DEFAULT uuid_generate_v4(),
    aud text NOT NULL,
    role text NOT NULL,
    email text,
    encrypted_password text,
    email_confirmed_at timestamptz,
    invited_at timestamptz,
    confirmation_token text,
    recovery_token text,
    email_change_token_current text,
    email_change_token_new text,
    last_sign_in_at timestamptz,
    raw_app_meta_data jsonb,
    raw_user_meta_data jsonb,
    is_super_admin boolean DEFAULT false,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now(),
    phone text,
    phone_confirmed_at timestamptz,
    phone_change text,
    phone_change_token_current text,
    phone_change_token_new text,
    phone_change_send_at timestamptz,
    phone_change_verified_at timestamptz,
    email_change_send_at timestamptz,
    email_change_verified_at timestamptz,
    banned_until timestamptz,
    reauthentication_token text,
    reauthentication_token_send_at timestamptz
);

-- Insert the avatars bucket
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
    'avatars',
    'avatars',
    true,
    5242880, -- 5MB
    ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp']
) ON CONFLICT (id) DO NOTHING;

-- Grant permissions
GRANT ALL ON SCHEMA public TO PUBLIC;
GRANT ALL ON ALL TABLES IN SCHEMA public TO PUBLIC;
GRANT ALL ON ALL TABLES IN SCHEMA storage TO PUBLIC;
GRANT ALL ON ALL TABLES IN SCHEMA auth TO PUBLIC;

-- Create indexes
CREATE INDEX IF NOT EXISTS storage_objects_bucket_id_idx ON storage.objects(bucket_id);
CREATE INDEX IF NOT EXISTS storage_objects_name_idx ON storage.objects(name);
CREATE INDEX IF NOT EXISTS auth_users_email_idx ON auth.users(email);

-- Create roles
CREATE ROLE IF NOT EXISTS anon;
CREATE ROLE IF NOT EXISTS authenticated;
CREATE ROLE IF NOT EXISTS service_role;

-- Grant roles
GRANT USAGE ON SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA storage TO anon, authenticated, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA auth TO anon, authenticated, service_role;

EOF

echo "✅ Supabase database initialized successfully!"