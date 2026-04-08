<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════════
    // LIST  —  GET /api/courses
    // ═══════════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $courses = Course::with(['lecturer:id,name', 'department:id,name,code'])
            ->withCount('students')
            ->when($request->department_id, fn($q) =>
                $q->where('department_id', $request->department_id)
            )
            ->when($request->lecturer_id, fn($q) =>
                $q->where('lecturer_id', $request->lecturer_id)
            )
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'code'           => $c->code,
                'title'          => $c->title,
                'lecturer'       => $c->lecturer?->name,
                'lecturer_id'    => $c->lecturer_id,
                'department'     => $c->department?->name,
                'department_id'  => $c->department_id,
                'students_count' => $c->students_count,
            ]);

        return response()->json($courses);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SHOW  —  GET /api/courses/{course}
    // ═══════════════════════════════════════════════════════════════════════════

    public function show(Course $course)
    {
        $course->load(['lecturer:id,name,email', 'department:id,name,code'])
               ->loadCount('students');

        return response()->json($course);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CREATE  —  POST /api/courses  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:20|unique:courses,code',
            'title'         => 'required|string|max:200',
            'lecturer_id'   => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        $lecturer = User::findOrFail($request->lecturer_id);
        if ($lecturer->role !== 'LECTURER') {
            return response()->json(['message' => 'Assigned user must have the LECTURER role.'], 422);
        }

        // Ensure lecturer belongs to the same department
        $dept = Department::findOrFail($request->department_id);

        $course = Course::create($request->only('code', 'title', 'lecturer_id', 'department_id'));

        return response()->json([
            'message' => 'Course created successfully.',
            'course'  => $course->load('lecturer:id,name', 'department:id,name'),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // UPDATE  —  PUT /api/courses/{course}  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'code'          => "sometimes|string|max:20|unique:courses,code,{$course->id}",
            'title'         => 'sometimes|string|max:200',
            'lecturer_id'   => 'sometimes|exists:users,id',
            'department_id' => 'sometimes|exists:departments,id',
        ]);

        if ($request->filled('lecturer_id')) {
            $lecturer = User::findOrFail($request->lecturer_id);
            if ($lecturer->role !== 'LECTURER') {
                return response()->json(['message' => 'Assigned user must have the LECTURER role.'], 422);
            }
        }

        $course->update($request->only('code', 'title', 'lecturer_id', 'department_id'));

        return response()->json(['message' => 'Course updated successfully.']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DELETE  —  DELETE /api/courses/{course}  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function destroy(Course $course)
    {
        if ($course->results()->where('status', 'approved')->exists()) {
            return response()->json([
                'message' => 'Cannot delete a course with approved results.',
            ], 422);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ASSIGN LECTURER  —  PATCH /api/courses/{course}/assign-lecturer  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function assignLecturer(Request $request, Course $course)
    {
        $request->validate([
            'lecturer_id' => 'required|exists:users,id',
        ]);

        $lecturer = User::findOrFail($request->lecturer_id);

        if ($lecturer->role !== 'LECTURER') {
            return response()->json(['message' => 'User must have the LECTURER role.'], 422);
        }

        $course->update(['lecturer_id' => $lecturer->id]);

        return response()->json([
            'message' => "{$lecturer->name} has been assigned to {$course->code}.",
        ]);
    }
}
