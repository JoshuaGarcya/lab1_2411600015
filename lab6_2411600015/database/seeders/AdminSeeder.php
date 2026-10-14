<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gscsda.test'],
            [
                'name' => 'Portal Admin',
                'password' => Hash::make('password123'),
            ]
        );

        // is_admin is not mass assignable on purpose, so set it explicitly
        $admin->forceFill(['is_admin' => true])->save();
    }
}
