<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Slot;
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

        User::factory()->create([
            'username' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            ArticleTestSeeder::class,
        ]);

        Customer::factory()->count(10)->create();
        Slot::factory()->count(10)->create();
    }
}
