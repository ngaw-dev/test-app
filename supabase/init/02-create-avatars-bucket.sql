-- Create avatars storage bucket for Supabase

-- Insert the avatars bucket into storage.buckets
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'avatars',
  'avatars',
  true,
  5242880, -- 5MB
  ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp']
) ON CONFLICT (id) DO UPDATE SET
  public = true,
  file_size_limit = 5242880,
  allowed_mime_types = ARRAY['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

-- Create RLS policies for the avatars bucket
-- Allow public read access to avatars
CREATE POLICY "Public Access" ON storage.objects FOR SELECT
USING (bucket_id = 'avatars');

-- Allow authenticated users to upload avatars
CREATE POLICY "Users can upload avatars" ON storage.objects FOR INSERT
WITH CHECK (
  bucket_id = 'avatars'
  AND (auth.role() = 'authenticated' OR auth.role() = 'service_role')
);

-- Allow users to update their own avatars
CREATE POLICY "Users can update own avatars" ON storage.objects FOR UPDATE
USING (
  bucket_id = 'avatars'
  AND (auth.role() = 'authenticated' OR auth.role() = 'service_role')
);

-- Allow users to delete their own avatars
CREATE POLICY "Users can delete own avatars" ON storage.objects FOR DELETE
USING (
  bucket_id = 'avatars'
  AND (auth.role() = 'authenticated' OR auth.role() = 'service_role')
);

-- Grant necessary permissions
GRANT ALL ON storage.buckets TO authenticated, anon, service_role;
GRANT ALL ON storage.objects TO authenticated, anon, service_role;