<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

function jsonRequest(): Request
{
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept', 'application/json');

    return $request;
}

it('renders authentication exceptions', function () {
    $response = ApiException::render(jsonRequest(), new AuthenticationException());

    expect($response->getStatusCode())->toBe(401);
    expect($response->getData(true)['error']['code'])->toBe('unauthenticated');
});

it('renders validation exceptions', function () {
    $validation = ValidationException::withMessages(['name' => ['Required']]);

    $response = ApiException::render(jsonRequest(), $validation);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(422);
    expect($data['error']['code'])->toBe('validation_failed');
    expect($data['errors'])->toHaveKey('name');
});

it('renders not found exceptions', function () {
    $exception = (new ModelNotFoundException())->setModel(\App\Models\User::class);

    $response = ApiException::render(jsonRequest(), $exception);

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('resource_not_found');
});

it('renders authorization exceptions', function () {
    $response = ApiException::render(jsonRequest(), new AuthorizationException('Denied'));

    expect($response->getStatusCode())->toBe(403);
    expect($response->getData(true)['error']['code'])->toBe('forbidden');
    expect($response->getData(true)['error']['message'])->toBe('Denied');
});

it('renders http exceptions', function () {
    Config::set('app.debug', true);

    $response = ApiException::render(jsonRequest(), new HttpException(418, 'Nope'));

    expect($response->getStatusCode())->toBe(418);
    expect($response->getData(true)['error']['code'])->toBe('http_exception');
});

it('renders query exceptions', function () {
    Config::set('app.debug', true);

    $queryException = new QueryException(connectionName: 'testing', sql: 'select 1', bindings: [], previous: new Exception('db fail'));

    $response = ApiException::render(jsonRequest(), $queryException);

    expect($response->getStatusCode())->toBe(500);
    expect($response->getData(true)['error']['code'])->toBe('database_error');
});
