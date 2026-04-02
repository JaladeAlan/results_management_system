<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hodCs   = User::where('email', 'hod.cs@university.edu')->first();
        $hodMath = User::where('email', 'hod.math@university.edu')->first();

        Department::create([
            'name'   => 'Computer Science',
            'code'   => 'CSC',
            'hod_id' => $hodCs->id,
        ]);

        Department::create([
            'name'   => 'Mathematics',
            'code'   => 'MTH',
            'hod_id' => $hodMath->id,
        ]);
    }
}
