<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Users\CreateUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => 'test@me.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->call([
            UserSeeder::class,
        ]);
    }
}
