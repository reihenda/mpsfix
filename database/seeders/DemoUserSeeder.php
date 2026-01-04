<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if demo user already exists
        if (User::where('email', 'demo@example.com')->exists()) {
            $this->command->info('Demo user already exists, skipping...');
            return;
        }

        // Create demo user
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('demo123'),
            'role' => 'demo',
            'harga_per_meter_kubik' => 5000,
            'tekanan_keluar' => 3.0,
            'suhu' => 25.0,
            'koreksi_meter' => 4.0311,
            'total_deposit' => 5000000,
            'total_purchases' => 0,
            'deposit_history' => json_encode([
                [
                    'date' => now()->format('Y-m-d H:i:s'),
                    'amount' => 5000000,
                    'description' => 'Initial deposit for demo account'
                ]
            ]),
            'pricing_history' => json_encode([
                [
                    'date' => now()->format('Y-m-d H:i:s'),
                    'year_month' => now()->format('Y-m'),
                    'harga_per_meter_kubik' => 5000,
                    'tekanan_keluar' => 3.0,
                    'suhu' => 25.0,
                    'koreksi_meter' => 4.0311
                ]
            ])
        ]);

        $this->command->info('Demo user created successfully!');
    }
}
