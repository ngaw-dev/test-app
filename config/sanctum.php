<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Applications using Sanctum may be stateful or stateless. Stateless
    | applications will not store session information or cookies and
    | typically authenticate users via a token. Stateful applications
    | will store session information and typically authenticate users via
    | a session cookie.
    |
    | Your application's stateful domains are the domains your / middleware
    | will run within. This value is used for encrypting cookies.
    | Typically, this should be the same value as your APP_URL.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost://localhost',
        env('APP_URL') ? '://'.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | authenticating users using Sanctum. These guards should be consistent
    | with the guards defined in your "auth.php" configuration file.
    |
    */

    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, tokens will never expire.
    | This will override any values set in the "access_tokens" table.
    |
    */

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', null),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party routes with session cookies or Sanctum
    | tokens, you may specify which authentication guard Sanctum should use.
    | The middleware defined here will be applied to every route that
    | is assigned the "sanctum" authentication guard.
    |
    */

    'middleware' => [
        'verify_csrf_token' => Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
    ],

];
