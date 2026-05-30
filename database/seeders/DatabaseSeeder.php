<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Guru accounts
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@brainstudy.id',
            'password' => Hash::make('guru123'),
            'role' => 'guru',
            'nip' => '198501012010011001',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@brainstudy.id',
            'password' => Hash::make('guru123'),
            'role' => 'guru',
            'nip' => '199003152015012002',
            'is_active' => true,
        ]);
    }
}
