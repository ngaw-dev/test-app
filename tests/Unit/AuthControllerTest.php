<?php

namespace Tests\Unit;

use App\Http\Controllers\API\AuthController;
use App\Services\SupabaseAuthService;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected AuthController $controller;
    protected SupabaseAuthService $mockSupabaseAuth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSupabaseAuth = $this->createMock(SupabaseAuthService::class);
        $this->controller = new AuthController($this->mockSupabaseAuth);
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(AuthController::class, $this->controller);
    }

    /** @test */
    public function it_has_register_method()
    {
        $this->assertTrue(method_exists($this->controller, 'register'));
    }

    /** @test */
    public function it_has_login_method()
    {
        $this->assertTrue(method_exists($this->controller, 'login'));
    }

    /** @test */
    public function it_has_user_method()
    {
        $this->assertTrue(method_exists($this->controller, 'user'));
    }

    /** @test */
    public function it_has_logout_method()
    {
        $this->assertTrue(method_exists($this->controller, 'logout'));
    }

    /** @test */
    public function it_has_forgotPassword_method()
    {
        $this->assertTrue(method_exists($this->controller, 'forgotPassword'));
    }

    /** @test */
    public function it_has_refresh_method()
    {
        $this->assertTrue(method_exists($this->controller, 'refresh'));
    }

    /** @test */
    public function it_has_updateProfile_method()
    {
        $this->assertTrue(method_exists($this->controller, 'updateProfile'));
    }
}