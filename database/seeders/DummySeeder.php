<?php

namespace Database\Seeders;

use App\Models\DummyModel;
use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DummyModel::factory()->count(5)->create();
    }
}
