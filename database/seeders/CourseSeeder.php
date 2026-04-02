<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $csDept   = Department::where('code', 'CSC')->first();
        $mathDept = Department::where('code', 'MTH')->first();

        $tunde  = User::where('email', 'tunde.balogun@university.edu')->first();
        $amaka  = User::where('email', 'amaka.nwosu@university.edu')->first();
        $emeka  = User::where('email', 'emeka.okafor@university.edu')->first();
        $fatima = User::where('email', 'fatima.aliyu@university.edu')->first();
        $segun  = User::where('email', 'segun.adeleke@university.edu')->first();

        // ── Computer Science courses ──────────────────────────────────────
        Course::create([
            'code'          => 'CSC101',
            'title'         => 'Introduction to Computing',
            'lecturer_id'   => $tunde->id,
            'department_id' => $csDept->id,
        ]);

        Course::create([
            'code'          => 'CSC201',
            'title'         => 'Data Structures and Algorithms',
            'lecturer_id'   => $amaka->id,
            'department_id' => $csDept->id,
        ]);

        Course::create([
            'code'          => 'CSC301',
            'title'         => 'Database Management Systems',
            'lecturer_id'   => $emeka->id,
            'department_id' => $csDept->id,
        ]);

        Course::create([
            'code'          => 'CSC401',
            'title'         => 'Software Engineering',
            'lecturer_id'   => $tunde->id,
            'department_id' => $csDept->id,
        ]);

        // ── Mathematics courses ───────────────────────────────────────────
        Course::create([
            'code'          => 'MTH101',
            'title'         => 'Elementary Mathematics I',
            'lecturer_id'   => $fatima->id,
            'department_id' => $mathDept->id,
        ]);

        Course::create([
            'code'          => 'MTH201',
            'title'         => 'Linear Algebra',
            'lecturer_id'   => $segun->id,
            'department_id' => $mathDept->id,
        ]);
    }
}
