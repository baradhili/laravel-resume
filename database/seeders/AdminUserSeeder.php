<?php

namespace Database\Seeders;

use App\Models\User; // Import the User Model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Import the Hash Facade
use Illuminate\Support\Str; // Import Str helper if needed for unique fields like remember_token

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the admin user
        User::factory()->create([
            'name' => 'Admin User', // Or whatever name you prefer
            'email' => 'admin@example.com', // Use your desired admin email
            'password' => Hash::make('password'), // Hash the password!
            'email_verified_at' => now(), // Optionally mark as verified
            'remember_token' => Str::random(60), // Optionally set a remember token
            // Add other fields if your User model has them (e.g., 'role' => 'admin')
        ]);

        // Optional: Create multiple users using factory()
        // User::factory(5)->create(); // Creates 5 regular users with random data
    }
}