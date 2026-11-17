<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Satu-satunya akun bawaan: Admin. Akun Talenta dan Penyedia dibuat lewat halaman register.
 * Aman dijalankan ulang (dicocokkan lewat email).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tandang.id'],
            [
                'name' => 'Administrator Tandang',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
