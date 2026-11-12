<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::updateOrCreate(
            ['email' => 'admin@haomilk.vn'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
            ]
        );

        // Demo customer account
        User::updateOrCreate(
            ['email' => 'khach@haomilk.vn'],
            [
                'name' => 'Khách Demo',
                'phone' => '0912345678',
                'password' => Hash::make('Khach@12345'),
                'role' => 'customer',
            ]
        );
    }
}
