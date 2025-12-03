<?php

use App\Models\DummyModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $guard = config('permission.default_guard', config('auth.defaults.guard', 'sanctum'));
    Permission::findOrCreate('dummy-model.create', $guard);
    Permission::findOrCreate('dummy-model.update', $guard);
    Permission::findOrCreate('dummy-model.delete', $guard);
});

it('allows anyone to list dummy models', function () {
    DummyModel::factory()->count(3)->create();

    $this->getJson('/api/dummy-models')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'from',
                'per_page',
                'total',
                'to',
                'last_page',
                'links' => [
                    ['url', 'label', 'page', 'active'],
                ],
                'path',
            ],
        ]);
});

it('allows anyone to view a dummy model', function () {
    $dummy = DummyModel::factory()->create();

    $this->getJson("/api/dummy-models/{$dummy->id}")
        ->assertOk()
        ->assertJsonPath('id', $dummy->id);
});

it('blocks creating without permission', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/dummy-models', [
        'name' => 'No Access',
    ])->assertForbidden();
});

it('allows creating with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('dummy-model.create');
    Sanctum::actingAs($user);

    $this->postJson('/api/dummy-models', [
        'name' => 'Allowed',
    ])->assertCreated()
        ->assertJsonPath('name', 'Allowed');
});

it('requires update permission', function () {
    $dummy = DummyModel::factory()->create(['name' => 'Old']);

    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/dummy-models/{$dummy->id}", [
        'name' => 'New',
    ])->assertForbidden();
});

it('allows updates with permission', function () {
    $dummy = DummyModel::factory()->create(['name' => 'Old']);
    $user = User::factory()->create();
    $user->givePermissionTo('dummy-model.update');
    Sanctum::actingAs($user);

    $this->putJson("/api/dummy-models/{$dummy->id}", [
        'name' => 'Updated',
    ])->assertOk()
        ->assertJsonPath('name', 'Updated');
});

it('requires delete permission', function () {
    $dummy = DummyModel::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/dummy-models/{$dummy->id}")
        ->assertForbidden();
});

it('allows deletes with permission', function () {
    $dummy = DummyModel::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('dummy-model.delete');
    Sanctum::actingAs($user);

    $this->deleteJson("/api/dummy-models/{$dummy->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('dummy_models', ['id' => $dummy->id]);
});
