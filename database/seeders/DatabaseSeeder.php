<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StatesLgaSeeder::class,
            CategorySeeder::class,
            ChallengeDemoSeeder::class,
        ]);

        User::factory()->admin()->create([
            'name' => 'Challenge Admin',
            'email' => 'admin@tunse.test',
        ]);

        User::factory()->auditor()->create([
            'name' => 'Challenge Auditor',
            'email' => 'auditor@tunse.test',
        ]);
    }
}
