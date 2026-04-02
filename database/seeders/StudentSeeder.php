<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $csDept   = Department::where('code', 'CSC')->first();
        $mathDept = Department::where('code', 'MTH')->first();

        // ── Computer Science students ─────────────────────────────────────
        $csStudents = [
            ['name' => 'Adaeze Okonkwo',    'matric' => 'CSC/2021/001', 'email' => 'adaeze.okonkwo@student.edu'],
            ['name' => 'Babatunde Lawal',   'matric' => 'CSC/2021/002', 'email' => 'babatunde.lawal@student.edu'],
            ['name' => 'Chioma Eze',        'matric' => 'CSC/2021/003', 'email' => 'chioma.eze@student.edu'],
            ['name' => 'David Nwosu',       'matric' => 'CSC/2021/004', 'email' => 'david.nwosu@student.edu'],
            ['name' => 'Esther Abiodun',    'matric' => 'CSC/2021/005', 'email' => 'esther.abiodun@student.edu'],
            ['name' => 'Femi Adebayo',      'matric' => 'CSC/2021/006', 'email' => 'femi.adebayo@student.edu'],
            ['name' => 'Grace Onyeka',      'matric' => 'CSC/2021/007', 'email' => 'grace.onyeka@student.edu'],
            ['name' => 'Hassan Musa',       'matric' => 'CSC/2021/008', 'email' => 'hassan.musa@student.edu'],
            ['name' => 'Ifeoma Chukwu',     'matric' => 'CSC/2021/009', 'email' => 'ifeoma.chukwu@student.edu'],
            ['name' => 'John Okoye',        'matric' => 'CSC/2021/010', 'email' => 'john.okoye@student.edu'],
        ];

        foreach ($csStudents as $data) {
            Student::create(array_merge($data, ['department_id' => $csDept->id]));
        }

        // ── Mathematics students ──────────────────────────────────────────
        $mathStudents = [
            ['name' => 'Kemi Ogundimu',     'matric' => 'MTH/2021/001', 'email' => 'kemi.ogundimu@student.edu'],
            ['name' => 'Ladi Abdullahi',    'matric' => 'MTH/2021/002', 'email' => 'ladi.abdullahi@student.edu'],
            ['name' => 'Miracle Ibe',       'matric' => 'MTH/2021/003', 'email' => 'miracle.ibe@student.edu'],
            ['name' => 'Ngozi Okeke',       'matric' => 'MTH/2021/004', 'email' => 'ngozi.okeke@student.edu'],
            ['name' => 'Ola Fashola',       'matric' => 'MTH/2021/005', 'email' => 'ola.fashola@student.edu'],
            ['name' => 'Peter Uzor',        'matric' => 'MTH/2021/006', 'email' => 'peter.uzor@student.edu'],
        ];

        foreach ($mathStudents as $data) {
            Student::create(array_merge($data, ['department_id' => $mathDept->id]));
        }
    }
}
