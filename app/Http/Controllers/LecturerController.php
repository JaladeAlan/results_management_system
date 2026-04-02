<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    /**
     * GET /api/lecturers/{lecturerId}/courses
     * Retrieve all courses assigned to a lecturer.
     */
    public function getCourses($lecturerId)
    {
        $courses = Course::where('lecturer_id', $lecturerId)
            ->with('lecturer:id,name')
            ->get()
            ->map(fn($course) => [
                'id'       => $course->id,
                'code'     => $course->code,
                'title'    => $course->title,
                'lecturer' => $course->lecturer?->name,
            ]);

        return response()->json($courses);
    }

    /**
     * GET /api/courses/{courseId}/students
     * Fetch all students registered for a specific course.
     */
    public function getCourseStudents($courseId)
    {
        $course = Course::findOrFail($courseId);

        $students = $course->students()
            ->get()
            ->map(fn($student) => [
                'studentId' => $student->id,
                'name'      => $student->name,
                'matric'    => $student->matric,
            ]);

        return response()->json($students);
    }

    /**
     * POST /api/courses/{courseId}/results
     * Submit CA and Exam scores for students in a course.
     */
    public function uploadResults(Request $request, $courseId)
    {
        Course::findOrFail($courseId);

        $request->validate([
            'scores'          => 'required|array|min:1',
            'scores.*.matric' => 'required|string|exists:students,matric',
            'scores.*.ca'     => 'required|numeric|min:0|max:30',
            'scores.*.exam'   => 'required|numeric|min:0|max:70',
        ]);

        foreach ($request->scores as $score) {
            $student = Student::where('matric', $score['matric'])->first();

            Result::updateOrCreate(
                ['course_id' => $courseId, 'student_id' => $student->id],
                [
                    'ca'     => $score['ca'],
                    'exam'   => $score['exam'],
                    'total'  => $score['ca'] + $score['exam'],
                    'status' => 'pending',
                ]
            );
        }

        return response()->json(['message' => 'Results uploaded successfully.']);
    }

    /**
     * GET /api/courses/{courseId}/results/download
     * Download course results as a CSV file.
     */
    public function downloadResults($courseId)
    {
        $course  = Course::findOrFail($courseId);
        $results = Result::where('course_id', $courseId)
            ->with('student:id,name,matric')
            ->get();

        $filename = "results_{$course->code}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($results) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Matric', 'Name', 'CA', 'Exam', 'Total', 'Status']);
            foreach ($results as $result) {
                fputcsv($handle, [
                    $result->student->matric,
                    $result->student->name,
                    $result->ca,
                    $result->exam,
                    $result->total,
                    $result->status,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
