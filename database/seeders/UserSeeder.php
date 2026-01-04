<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat SuperAdmin
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_SUPER_ADMIN
        ]);

        // Buat Admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN
        ]);

        // Buat beberapa Customer
        $customers = [
            [
                'name' => 'Customer 1',
                'email' => 'customer1@example.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_CUSTOMER
            ],
            [
                'name' => 'Customer 2',
                'email' => 'customer2@example.com',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_CUSTOMER
            ]
        ];

        foreach ($customers as $customerData) {
            User::create($customerData);
        }
    }
}
