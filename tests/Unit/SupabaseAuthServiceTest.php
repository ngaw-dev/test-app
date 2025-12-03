<?php

namespace Tests\Unit;

use App\Services\SupabaseAuthService;
use Tests\TestCase;

class SupabaseAuthServiceTest extends TestCase
{
    protected SupabaseAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SupabaseAuthService();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(SupabaseAuthService::class, $this->service);
    }

    /** @test */
    public function it_has_signUp_method()
    {
        $this->assertTrue(method_exists($this->service, 'signUp'));
    }

    /** @test */
    public function it_has_signIn_method()
    {
        $this->assertTrue(method_exists($this->service, 'signIn'));
    }

    /** @test */
    public function it_has_getUser_method()
    {
        $this->assertTrue(method_exists($this->service, 'getUser'));
    }

    /** @test */
    public function it_has_signOut_method()
    {
        $this->assertTrue(method_exists($this->service, 'signOut'));
    }

    /** @test */
    public function it_has_resetPassword_method()
    {
        $this->assertTrue(method_exists($this->service, 'resetPassword'));
    }

    /** @test */
    public function it_has_refreshToken_method()
    {
        $this->assertTrue(method_exists($this->service, 'refreshToken'));
    }

    /** @test */
    public function it_has_updateUser_method()
    {
        $this->assertTrue(method_exists($this->service, 'updateUser'));
    }
}