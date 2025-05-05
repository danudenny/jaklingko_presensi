<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@jaklingko.com',
            'password' => Hash::make('password'),
            'is_superadmin' => true,
        ]);

        // Create regular admin user
        User::create([
            'name' => 'Regular Admin',
            'email' => 'user@jaklingko.com',
            'password' => Hash::make('password'),
            'is_superadmin' => false,
        ]);
    }
}
