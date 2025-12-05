# Supabase Integration for Laravel

This document explains how to set up and use Supabase with your Laravel application.

## Overview

This integration provides:
- **Authentication**: Supabase Auth with JWT tokens
- **Database**: PostgreSQL database with logical replication
- **Storage**: File storage with S3-compatible API
- **Realtime**: Real-time subscriptions and WebSocket connections
- **Studio**: Web-based management interface

## Quick Start

### 1. Start Supabase Services

```bash
# Start all Supabase services
docker-compose -f docker-compose.supabase.yml up -d

# Check service status
docker-compose -f docker-compose.supabase.yml ps
```

### 2. Configure Environment

Copy the Supabase configuration to your `.env` file:

```bash
cp .env.example .env
# Update the following variables in .env:
```

### 3. Run Laravel Migrations

```bash
php artisan migrate
```

### 4. Test the Setup

```bash
# Start Laravel development server
php artisan serve

# Test API endpoints in another terminal
curl -X POST http://localhost:8000/api/supabase/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

## Services and Ports

| Service | Port | Description |
|---------|------|-------------|
| Kong Gateway | 64322 | Main API gateway |
| Kong SSL | 64323 | HTTPS endpoint |
| PostgreSQL | 64320 | Database |
| Auth | 64324 | Authentication |
| REST API | 64325 | PostgREST |
| Storage | 64326 | File storage |
| Functions | 64327 | Edge functions |
| Studio | 64328 | Management UI |
| Meta | 64329 | Postgres meta |
| Realtime | 64330 | WebSocket server |
| Image Proxy | 64332 | Image processing |
| Mail | 64333 | SMTP server |

## Environment Variables

### Database Connection
```env
DB_CONNECTION=pgsql
DB_HOST=supabase-dev.local
DB_PORT=64320
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-super-secret-and-long-postgres-password
```

### Supabase Configuration
```env
SUPABASE_URL=http://supabase-dev.local:64322
SUPABASE_SECRET_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_JWT_SECRET=your-super-secret-jwt-token-with-at-least-32-characters-long
SUPABASE_STUDIO_URL=http://supabase-dev.local
```

### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=supabase-mail
MAIL_PORT=2500
MAIL_USERNAME=fake_mail_user
MAIL_PASS=fake_mail_password
```

## API Endpoints

### Authentication

All authentication endpoints are under `/api/supabase/auth/`:

- `POST /register` - Register new user
- `POST /login` - Authenticate user
- `GET /me` - Get current user (requires auth)
- `POST /logout` - Logout user (requires auth)
- `POST /forgot-password` - Request password reset
- `PUT /profile` - Update user profile (requires auth)

### Example Usage

#### Register User
```bash
curl -X POST http://localhost:8000/api/supabase/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "data": {
      "first_name": "John",
      "last_name": "Doe"
    }
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/supabase/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

#### Get User Profile
```bash
curl -X GET http://localhost:8000/api/supabase/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Laravel Integration

### SupabaseService

The `App\Services\SupabaseService` class provides a convenient interface to interact with Supabase:

```php
use App\Services\SupabaseService;

class YourController extends Controller
{
    public function __construct(private SupabaseService $supabase) {}

    public function createUser()
    {
        $user = $this->supabase->createUser([
            'email' => 'user@example.com',
            'password' => 'password123',
            'user_metadata' => ['role' => 'user']
        ]);

        return response()->json($user);
    }
}
```

### Database Operations

Use the REST client for database operations:

```php
public function fetchData()
{
    $response = $this->supabase->getRestClient()
        ->get('your_table')
        ->json();

    return response()->json($response);
}
```

### Middleware

Use the `supabase.auth` middleware to protect routes:

```php
Route::middleware('supabase.auth')->group(function () {
    Route::get('/protected-data', [DataController::class, 'index']);
});
```

## Development

### Accessing Studio

Visit `http://supabase-dev.local` in your browser to access the Supabase Studio management interface.

### Database Access

```bash
# Connect to PostgreSQL
docker exec -it supabase_db psql -U postgres -d postgres

# View logs
docker logs supabase_auth
```

### Resetting Services

```bash
# Stop and remove all containers
docker-compose -f docker-compose.supabase.yml down -v

# Remove volumes
docker volume rm test-app_db test-app_storage test-app_analytics

# Start fresh
docker-compose -f docker-compose.supabase.yml up -d
```

## Production Considerations

1. **Security**: Change all default passwords and secrets
2. **Domain**: Update `supabase-dev.local` to your actual domain
3. **SSL**: Configure SSL certificates for production
4. **Backups**: Set up automated database backups
5. **Monitoring**: Implement monitoring and alerting

## Troubleshooting

### Common Issues

1. **Connection refused**: Ensure all services are running
2. **Authentication errors**: Check JWT secret configuration
3. **Database errors**: Verify PostgreSQL is accessible
4. **Port conflicts**: Ensure ports 64320-64335 are available

### Health Checks

```bash
# Check individual service health
curl http://localhost:64324/health  # Auth service
curl http://localhost:64325/ready   # REST API
curl http://localhost:64326/status  # Storage
```

## Integration with Existing Laravel Auth

This setup runs alongside your existing Laravel Sanctum authentication:

- `/api/auth/*` - Laravel Sanctum routes
- `/api/supabase/auth/*` - Supabase Auth routes

You can gradually migrate or use both systems simultaneously based on your needs.

## Additional Resources

- [Supabase Documentation](https://supabase.com/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [PostgREST Documentation](https://postgrest.org/)