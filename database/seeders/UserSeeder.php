<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            [
                'email' => 'admin@admin.com',
            ],
            [
                'name' => 'Admin',
                'password' => bcrypt('P@ssw0rd'),
                'email_verified_at' => now(),
            ]
        );

        //sync roles
        $user->assignRole('super_admin');
    }
}