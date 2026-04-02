<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Seeder;

class CourseStudentSeeder extends Seeder
{
    public function run(): void
    {
        $csStudents   = Student::where('department_id', function ($q) {
            $q->select('id')->from('departments')->where('code', 'CSC');
        })->pluck('id')->toArray();

        $mathStudents = Student::where('department_id', function ($q) {
            $q->select('id')->from('departments')->where('code', 'MTH');
        })->pluck('id')->toArray();

        // Enroll all CS students in all CS courses
        Course::whereIn('code', ['CSC101', 'CSC201', 'CSC301', 'CSC401'])
            ->get()
            ->each(fn($course) => $course->students()->syncWithoutDetaching($csStudents));

        // Enroll all Math students in all Math courses
        Course::whereIn('code', ['MTH101', 'MTH201'])
            ->get()
            ->each(fn($course) => $course->students()->syncWithoutDetaching($mathStudents));

        // Cross-enroll: CS students take MTH101 (common service course)
        Course::where('code', 'MTH101')
            ->first()
            ->students()
            ->syncWithoutDetaching($csStudents);
    }
}
