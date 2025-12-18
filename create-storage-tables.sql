-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create schemas
CREATE SCHEMA IF NOT EXISTS storage;
CREATE SCHEMA IF NOT EXISTS auth;

-- Create storage.buckets table
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

-- Create storage.objects table
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

-- Insert the avatars bucket
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
    'avatars',
    'avatars',
    true,
    5242880,
    ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp']
) ON CONFLICT (id) DO NOTHING;

-- Grant permissions
GRANT ALL ON SCHEMA storage TO PUBLIC;
GRANT ALL ON ALL TABLES IN SCHEMA storage TO PUBLIC;
GRANT ALL ON SCHEMA auth TO PUBLIC;
GRANT ALL ON ALL TABLES IN SCHEMA auth TO PUBLIC;

-- Create indexes
CREATE INDEX IF NOT EXISTS storage_objects_bucket_id_idx ON storage.objects(bucket_id);
CREATE INDEX IF NOT EXISTS storage_objects_name_idx ON storage.objects(name);