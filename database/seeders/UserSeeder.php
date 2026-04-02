<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Registration Officers ─────────────────────────────────────────
        User::create([
            'name'     => 'Samuel Adeyemi',
            'email'    => 'ro@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'RO',
        ]);

        // ── Heads of Department ───────────────────────────────────────────
        User::create([
            'name'     => 'Prof. Chukwuemeka Obi',
            'email'    => 'hod.cs@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'HOD',
        ]);

        User::create([
            'name'     => 'Prof. Ngozi Eze',
            'email'    => 'hod.math@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'HOD',
        ]);

        // ── Lecturers ─────────────────────────────────────────────────────
        User::create([
            'name'     => 'Dr. Tunde Balogun',
            'email'    => 'tunde.balogun@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'LECTURER',
        ]);

        User::create([
            'name'     => 'Dr. Amaka Nwosu',
            'email'    => 'amaka.nwosu@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'LECTURER',
        ]);

        User::create([
            'name'     => 'Dr. Emeka Okafor',
            'email'    => 'emeka.okafor@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'LECTURER',
        ]);

        User::create([
            'name'     => 'Dr. Fatima Aliyu',
            'email'    => 'fatima.aliyu@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'LECTURER',
        ]);

        User::create([
            'name'     => 'Mr. Segun Adeleke',
            'email'    => 'segun.adeleke@university.edu',
            'password' => Hash::make('password'),
            'role'     => 'LECTURER',
        ]);
    }
}
