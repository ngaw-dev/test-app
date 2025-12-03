<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiException extends Exception
{
    private const DEFAULT_DOCS_URL = null;

    public static array $handlers = [
        AuthenticationException::class => 'handleAuthenticationException',
        ValidationException::class => 'handleValidationException',
        ModelNotFoundException::class => 'handleNotFoundException',
        NotFoundHttpException::class => 'handleNotFoundException',
        AuthorizationException::class => 'handleAuthorizationException',
        AccessDeniedHttpException::class => 'handleAccessDeniedException',
        MethodNotAllowedHttpException::class => 'handleMethodNotAllowedHttpException',
        HttpException::class => 'handleHttpException',
        QueryException::class => 'handleQueryException',
    ];

    private static function errorResponse(
        int $statusCode,
        string $type,
        string $errorCode,
        string $message,
        array $context = []
    ): JsonResponse {
        $errorPayload = array_merge([
            'type' => $type,
            'code' => $errorCode,
            'message' => $message,
        ], $context);

        if (! array_key_exists('doc_url', $errorPayload) && self::DEFAULT_DOCS_URL) {
            $errorPayload['doc_url'] = self::DEFAULT_DOCS_URL;
        }

        return response()->json([
            'success' => false,
            'error' => $errorPayload,
        ], $statusCode);
    }

    public static function handleAuthenticationException(AuthenticationException $e, Request $request): JsonResponse
    {
        $source = 'Line: ' . $e->getLine() . ', File: ' . $e->getFile();
        Log::notice(basename($e::class) . ' - ' . $e->getMessage() . ' - ' . $source);

        return self::errorResponse(
            statusCode: 401,
            type: 'authentication_error',
            errorCode: 'unauthenticated',
            message: 'Unauthenticated.'
        );
    }

    public static function handleValidationException(ValidationException $e, Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'validation_failed',
                'message' => 'Validation failed',
                'details' => [
                    'fields' => $e->errors(),
                ],
            ],
            'errors' => $e->errors(),
        ], 422);
    }

    public static function handleNotFoundException(ModelNotFoundException|NotFoundHttpException $e, Request $request): JsonResponse
    {
        return self::errorResponse(
            statusCode: 404,
            type: 'invalid_request_error',
            errorCode: 'resource_not_found',
            message: 'Resource not found',
            context: [
                'details' => [
                    'reason' => $e->getMessage(),
                ],
            ]
        );
    }

    public static function handleAuthorizationException(AuthorizationException $e, Request $request): JsonResponse
    {
        return self::errorResponse(
            statusCode: 403,
            type: 'authorization_error',
            errorCode: 'forbidden',
            message: $e->getMessage() ?: 'Forbidden'
        );
    }

    public static function handleAccessDeniedException(AccessDeniedHttpException $e, Request $request): JsonResponse
    {
        return self::errorResponse(
            statusCode: 403,
            type: 'authorization_error',
            errorCode: 'access_denied',
            message: $e->getMessage() ?: 'This action is unauthorized'
        );
    }

    public static function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e, Request $request): JsonResponse
    {
        return self::errorResponse(
            statusCode: 405,
            type: 'invalid_request_error',
            errorCode: 'method_not_allowed',
            message: 'Method not allowed',
            context: [
                'details' => [
                    'reason' => $e->getMessage(),
                ],
            ]
        );
    }

    public static function handleHttpException(HttpException $e, Request $request): JsonResponse
    {
        $context = [];
        $statusCode = $e->getStatusCode();

        if ($statusCode === 403) {
            return self::errorResponse(
                statusCode: 403,
                type: 'authorization_error',
                errorCode: 'forbidden',
                message: $e->getMessage() ?: 'Forbidden'
            );
        }

        if (config('app.debug')) {
            $context['details'] = [
                'debug' => $e->getMessage(),
                'status_code' => $statusCode,
            ];
        }

        return self::errorResponse(
            statusCode: $statusCode,
            type: $statusCode >= 500 ? 'server_error' : 'invalid_request_error',
            errorCode: 'http_exception',
            message: 'An unexpected application error occurred',
            context: $context
        );
    }

    public static function handleQueryException(QueryException $e, Request $request): JsonResponse
    {
        $previousMessage = $e->getPrevious()?->getMessage();
        $baseMessage = trim((string) $previousMessage);

        if ($baseMessage === '') {
            $baseMessage = 'Database error occurred';
        }

        $details = [
            'error' => $e->getMessage(),
        ];

        if (config('app.debug')) {
            $details['sql'] = $e->getSql();
            $details['bindings'] = $e->getBindings();
        }

        return self::errorResponse(
            statusCode: 500,
            type: 'server_error',
            errorCode: 'database_error',
            message: $baseMessage,
            context: [
                'details' => $details,
            ]
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public static function render(Request $request, Throwable $e): JsonResponse
    {
        $exceptionClass = get_class($e);

        if (isset(self::$handlers[$exceptionClass])) {
            $handlerMethod = self::$handlers[$exceptionClass];
            if (method_exists(self::class, $handlerMethod)) {
                return self::$handlerMethod($e, $request);
            }
        }

        foreach (self::$handlers as $class => $handlerMethod) {
            if ($e instanceof $class) {
                return self::$handlerMethod($e, $request);
            }
        }

        return self::errorResponse(
            statusCode: 500,
            type: 'server_error',
            errorCode: 'unknown_error',
            message: config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
        );
    }
}
