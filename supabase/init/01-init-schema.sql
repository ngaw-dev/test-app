-- Initialize Supabase database schema
-- This script runs when the database container starts

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Create schemas
CREATE SCHEMA IF NOT EXISTS auth;
CREATE SCHEMA IF NOT EXISTS storage;
CREATE SCHEMA IF NOT EXISTS _realtime;
CREATE SCHEMA IF NOT EXISTS extensions;
CREATE SCHEMA IF NOT EXISTS information_schema;

-- Create auth schema tables
CREATE TABLE IF NOT EXISTS auth.users (
    instance_id uuid NOT NULL,
    id uuid NOT NULL,
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
    is_super_admin bool,
    created_at timestamptz,
    updated_at timestamptz,
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
    reauthentication_token_send_at timestamptz,
    is_sso_user bool DEFAULT false,
    deleted_at timestamptz,
    CONSTRAINT users_instance_id_check CHECK ((instance_id IS NOT NULL)),
    CONSTRAINT users_id_check CHECK ((id IS NOT NULL)),
    CONSTRAINT users_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS auth.sessions (
    id bigint NOT NULL,
    user_id uuid NOT NULL,
    created_at timestamptz,
    updated_at timestamptz,
    token text NOT NULL,
    parent_session_id bigint,
    aal text,
    factor_id integer,
    CONSTRAINT sessions_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS auth.identities (
    provider_id text NOT NULL,
    user_id uuid NOT NULL,
    identity_data jsonb NOT NULL,
    provider text NOT NULL,
    last_sign_in_at timestamptz,
    created_at timestamptz,
    updated_at timestamptz,
    CONSTRAINT identities_pkey PRIMARY KEY (provider_id)
);

CREATE TABLE IF NOT EXISTS auth.refresh_tokens (
    instance_id uuid NOT NULL,
    token text NOT NULL,
    user_id uuid NOT NULL,
    revoked bool,
    created_at timestamptz,
    updated_at timestamptz,
    expires_at timestamptz,
    CONSTRAINT refresh_tokens_instance_id_check CHECK ((instance_id IS NOT NULL)),
    CONSTRAINT refresh_tokens_pkey PRIMARY KEY (token)
);

-- Create storage schema tables
CREATE TABLE IF NOT EXISTS storage.buckets (
    id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamptz,
    updated_at timestamptz,
    public bool DEFAULT false,
    file_size_limit bigint,
    allowed_mime_types text[],
    CONSTRAINT buckets_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS storage.objects (
    id uuid DEFAULT uuid_generate_v4() NOT NULL,
    bucket_id text NOT NULL,
    name text NOT NULL,
    owner uuid,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now(),
    last_accessed_at timestamptz,
    file_size bigint,
    etag text,
    metadata jsonb,
    CONSTRAINT objects_pkey PRIMARY KEY (id)
);

-- Create realtime schema tables
CREATE TABLE IF NOT EXISTS _realtime.channels (
    id bigint NOT NULL,
    name text NOT NULL,
    created_at timestamptz,
    updated_at timestamptz,
    inserted_at timestamptz,
    claims jsonb,
    realtime_topic text,
    realtime_private boolean DEFAULT false,
    realtime_extension text,
    realtime_extension_state jsonb,
    realtime_subtopic text,
    realtime_schema text,
    realtime_record jsonb,
    realtime_is_active boolean DEFAULT true,
    realtime_is_deleted boolean DEFAULT false,
    realtime_date timestamp with time zone DEFAULT now(),
    realtime_room text,
    realtime_message jsonb,
    realtime_hash text,
    CONSTRAINT channels_pkey PRIMARY KEY (id)
);

-- Set up permissions
GRANT USAGE ON SCHEMA auth TO authenticated, anon, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA auth TO authenticated, anon, service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA auth TO authenticated, anon, service_role;

GRANT USAGE ON SCHEMA storage TO authenticated, anon, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA storage TO authenticated, anon, service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA storage TO authenticated, anon, service_role;

GRANT USAGE ON SCHEMA _realtime TO authenticated, anon, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA _realtime TO authenticated, anon, service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA _realtime TO authenticated, anon, service_role;

-- Create helpful roles
CREATE ROLE IF NOT EXISTS anon NOINHERIT;
CREATE ROLE IF NOT EXISTS authenticated NOINHERIT;
CREATE ROLE IF NOT EXISTS service_role NOINHERIT LOGIN NOCREATEROLE NOCREATEDB NOSUPERUSER;

-- Grant permissions to roles
GRANT USAGE ON SCHEMA auth TO anon, authenticated, service_role;
GRANT USAGE ON SCHEMA storage TO anon, authenticated, service_role;
GRANT USAGE ON SCHEMA _realtime TO anon, authenticated, service_role;

-- Create initial bucket for storage
INSERT INTO storage.buckets (id, name, public) VALUES
('avatars', 'avatars', true),
('documents', 'documents', false)
ON CONFLICT (id) DO NOTHING;

-- Enable RLS (Row Level Security)
ALTER TABLE storage.buckets ENABLE ROW LEVEL SECURITY;
ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

-- Create RLS policies
CREATE POLICY "Anyone can view public buckets" ON storage.buckets FOR SELECT USING (public = true);
CREATE POLICY "Anyone can upload files to public buckets" ON storage.objects FOR INSERT WITH CHECK (bucket_id IN (SELECT id FROM storage.buckets WHERE public = true));
CREATE POLICY "Anyone can view files in public buckets" ON storage.objects FOR SELECT USING (bucket_id IN (SELECT id FROM storage.buckets WHERE public = true));
CREATE POLICY "Anyone can update files in public buckets" ON storage.objects FOR UPDATE USING (bucket_id IN (SELECT id FROM storage.buckets WHERE public = true));
CREATE POLICY "Anyone can delete files in public buckets" ON storage.objects FOR DELETE USING (bucket_id IN (SELECT id FROM storage.buckets WHERE public = true));