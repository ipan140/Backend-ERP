<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Data user default
        $users = [
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@example.com',
                'password' => '12345678', // tanpa hash
            ],
            [
                'name'     => 'HR Manager',
                'email'    => 'hr@example.com',
                'password' => '12345678',
            ],
            [
                'name'     => 'Finance Manager',
                'email'    => 'finance@example.com',
                'password' => '12345678',
            ],
            [
                'name'     => 'IT Support',
                'email'    => 'it@example.com',
                'password' => '12345678',
            ],
            [
                'name'     => 'Test User',
                'email'    => 'test@example.com',
                'password' => '12345678',
            ],
        ];

        // ✅ Simpan / update ke DB
        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => $data['password'], // tidak di-hash
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('✅ UserSeeder: Admin & user lainnya berhasil dibuat dengan password 12345678.');
    }
}
