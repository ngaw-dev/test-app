<?php

namespace Database\Seeders;

use App\Models\DummyModel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\PermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
        ]);

        User::factory(10)->create();
        DummyModel::factory(5)->create();

        User::factory()->create([
            'name' => 'Creator User',
            'email' => 'creator@example.com',
        ])->givePermissionTo('dummy-model.create');

        User::factory()->create([
            'name' => 'Updater User',
            'email' => 'updater@example.com',
        ])->givePermissionTo('dummy-model.update');

        User::factory()->create([
            'name' => 'Destroyer User',
            'email' => 'destroyer@example.com',
        ])->givePermissionTo('dummy-model.delete');

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
