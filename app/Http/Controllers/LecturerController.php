<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

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
     * GET /api/courses/{courseId}/results/template
     * Download a CSV template pre-filled with enrolled students and blank score columns.
     * The lecturer fills in CA1, CA2, and Exam, then re-uploads the file.
     */
    public function downloadTemplate($courseId)
    {
        $course   = Course::findOrFail($courseId);
        $students = $course->students()->orderBy('students.matric')->get(['students.id', 'students.name', 'students.matric']);

        $filename = "results_template_{$course->code}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($students, $course) {
            $handle = fopen('php://output', 'w');

            // Metadata rows so the lecturer knows the course context
            fputcsv($handle, ['# Course', $course->code, $course->title]);
            fputcsv($handle, ['# Scoring', 'CA1 max: 20', 'CA2 max: 20', 'Exam max: 60']);
            fputcsv($handle, []); // blank separator

            // Column headers — matric and name are read-only identifiers
            fputcsv($handle, ['matric', 'name', 'ca1', 'ca2', 'exam']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->matric,
                    $student->name,
                    '', // ca1  — to be filled
                    '', // ca2  — to be filled
                    '', // exam — to be filled
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * POST /api/courses/{courseId}/results
     *
     * Accepts scores in two formats:
     *   1. JSON body  — { "scores": [{ "matric", "ca1", "ca2", "exam" }, ...] }
     *   2. CSV upload — multipart/form-data with a "file" field containing the
     *                   filled-in template CSV (columns: matric, ca1, ca2, exam)
     *
     * Rules:
     *  - Only the lecturer assigned to the course may upload.
     *  - Re-upload is blocked once results are approved.
     *  - ca1 + ca2 = max 40 (each max 20), exam = max 60, total = max 100.
     */
    public function uploadResults(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        if ($course->lecturer_id !== JWTAuth::parseToken()->authenticate()->id) {
            return response()->json(['message' => 'You are not assigned to this course.'], 403);
        }

        // Block re-upload if results are already approved
        if (Result::where('course_id', $courseId)->where('status', 'approved')->exists()) {
            return response()->json([
                'message' => 'Results for this course have already been approved and cannot be re-uploaded.',
            ], 422);
        }

        // ── Determine input format ────────────────────────────────────────────
        if ($request->hasFile('scores')) {
            $scores = $this->parseCsvUpload($request);

            // $scores is either an array of rows or a JsonResponse (error)
            if (! is_array($scores)) {
                return $scores; // propagate the error response
            }
        } else {
            // Fall back to JSON body
            $request->validate([
                'scores'           => 'required|array|min:1',
                'scores.*.matric'  => 'required|string|exists:students,matric',
                'scores.*.ca1'     => 'required|numeric|min:0|max:20',
                'scores.*.ca2'     => 'required|numeric|min:0|max:20',
                'scores.*.exam'    => 'required|numeric|min:0|max:60',
            ]);

            $scores = $request->scores;
        }

        // ── Persist results ───────────────────────────────────────────────────
        $errors = [];

        foreach ($scores as $index => $score) {
            $student = Student::where('matric', $score['matric'])->first();

            if (! $student) {
                $errors[] = "Row " . ($index + 1) . ": matric '{$score['matric']}' not found.";
                continue;
            }

            $ca1  = (float) $score['ca1'];
            $ca2  = (float) $score['ca2'];
            $exam = (float) $score['exam'];

            Result::updateOrCreate(
                ['course_id' => $courseId, 'student_id' => $student->id],
                [
                    'ca1'    => $ca1,
                    'ca2'    => $ca2,
                    'exam'   => $exam,
                    'total'  => $ca1 + $ca2 + $exam,
                    'status' => 'pending',
                ]
            );
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Some rows had errors and were skipped.',
                'errors'  => $errors,
            ], 207); // 207 Multi-Status
        }

        return response()->json(['message' => 'Results uploaded successfully.']);
    }

    /**
     * Parse an uploaded CSV file and return an array of score rows.
     * Returns a JsonResponse on validation failure.
     *
     * Expected CSV columns (after skipping comment/blank rows):
     *   matric | name | ca1 | ca2 | exam
     * The "name" column is ignored — matric is the authoritative identifier.
     */
    private function parseCsvUpload(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'scores' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path   = $request->file('scores')->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return response()->json(['message' => 'Could not read the uploaded file.'], 422);
        }

        $scores     = [];
        $headerMap  = null; // column-name → index
        $rowNumber  = 0;
        $dataErrors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip blank rows and comment rows (starting with #)
            if (empty($row) || trim($row[0]) === '' || str_starts_with(trim($row[0]), '#')) {
                continue;
            }

            // First non-comment, non-blank row is the header
            if ($headerMap === null) {
                $headerMap = array_flip(array_map('strtolower', array_map('trim', $row)));
                continue;
            }

            // Validate required columns exist in header
            foreach (['matric', 'ca1', 'ca2', 'exam'] as $col) {
                if (! isset($headerMap[$col])) {
                    fclose($handle);
                    return response()->json([
                        'message' => "CSV is missing required column: \"{$col}\". " .
                                     "Expected header row: matric, name, ca1, ca2, exam",
                    ], 422);
                }
            }

            $matric = trim($row[$headerMap['matric']] ?? '');
            $ca1    = trim($row[$headerMap['ca1']]    ?? '');
            $ca2    = trim($row[$headerMap['ca2']]    ?? '');
            $exam   = trim($row[$headerMap['exam']]   ?? '');

            // Skip entirely empty data rows
            if ($matric === '' && $ca1 === '' && $ca2 === '' && $exam === '') {
                continue;
            }

            // Per-row validation
            $rowErrors = [];

            if ($matric === '') {
                $rowErrors[] = 'matric is required';
            }

            if (! is_numeric($ca1) || (float)$ca1 < 0 || (float)$ca1 > 20) {
                $rowErrors[] = "ca1 must be a number between 0 and 20 (got: \"{$ca1}\")";
            }

            if (! is_numeric($ca2) || (float)$ca2 < 0 || (float)$ca2 > 20) {
                $rowErrors[] = "ca2 must be a number between 0 and 20 (got: \"{$ca2}\")";
            }

            if (! is_numeric($exam) || (float)$exam < 0 || (float)$exam > 60) {
                $rowErrors[] = "exam must be a number between 0 and 60 (got: \"{$exam}\")";
            }

            if (! empty($rowErrors)) {
                $dataErrors["Row {$rowNumber} ({$matric})"] = $rowErrors;
                continue;
            }

            $scores[] = [
                'matric' => $matric,
                'ca1'    => (float) $ca1,
                'ca2'    => (float) $ca2,
                'exam'   => (float) $exam,
            ];
        }

        fclose($handle);

        if (empty($scores) && empty($dataErrors)) {
            return response()->json(['message' => 'The CSV file contains no data rows.'], 422);
        }

        if (! empty($dataErrors)) {
            return response()->json([
                'message' => 'CSV validation failed. Fix the errors and re-upload.',
                'errors'  => $dataErrors,
            ], 422);
        }

        return $scores;
    }

    /**
     * GET /api/courses/{courseId}/results/download
     * Download the submitted results (with grades) as a CSV file.
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
            fputcsv($handle, ['Matric', 'Name', 'CA1', 'CA2', 'Total CA', 'Exam', 'Total', 'Grade', 'Status']);
            foreach ($results as $result) {
                fputcsv($handle, [
                    $result->student->matric,
                    $result->student->name,
                    $result->ca1,
                    $result->ca2,
                    $result->total_ca,
                    $result->exam,
                    $result->total,
                    $result->grade,
                    $result->status,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}