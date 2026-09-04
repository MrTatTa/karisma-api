<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate([
            'name'      => 'Administrator',
            'username'  => 'admin',
            'password'  => Hash::make('birdman1'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
