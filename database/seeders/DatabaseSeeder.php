<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'saleh9090@gmail.com');
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            throw new RuntimeException('Set ADMIN_PASSWORD in the environment before seeding the admin user.');
        }

        User::updateOrCreate([
            'email' => $email,
        ], [
            'name' => env('ADMIN_NAME', 'saleh9090'),
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }
}
