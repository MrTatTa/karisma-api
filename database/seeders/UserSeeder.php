<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('birdman1'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
