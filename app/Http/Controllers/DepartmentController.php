<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════════
    // LIST  —  GET /api/departments
    // ═══════════════════════════════════════════════════════════════════════════

    public function index()
    {
        $departments = Department::with('hod:id,name,email')
            ->withCount(['courses', 'students'])
            ->get();

        return response()->json($departments);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SHOW  —  GET /api/departments/{department}
    // ═══════════════════════════════════════════════════════════════════════════

    public function show(Department $department)
    {
        $department->load('hod:id,name,email')
                   ->loadCount(['courses', 'students']);

        return response()->json($department);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CREATE  —  POST /api/departments  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:150|unique:departments,name',
            'code'   => 'required|string|max:10|unique:departments,code',
            'hod_id' => 'nullable|exists:users,id',
        ]);

        // Ensure assigned user has HOD role
        if ($request->hod_id) {
            $hod = User::findOrFail($request->hod_id);
            if ($hod->role !== 'HOD') {
                return response()->json(['message' => 'Assigned user must have the HOD role.'], 422);
            }
        }

        $department = Department::create($request->only('name', 'code', 'hod_id'));

        return response()->json([
            'message'    => 'Department created successfully.',
            'department' => $department->load('hod:id,name'),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // UPDATE  —  PUT /api/departments/{department}  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name'   => "sometimes|string|max:150|unique:departments,name,{$department->id}",
            'code'   => "sometimes|string|max:10|unique:departments,code,{$department->id}",
            'hod_id' => 'nullable|exists:users,id',
        ]);

        if ($request->filled('hod_id')) {
            $hod = User::findOrFail($request->hod_id);
            if ($hod->role !== 'HOD') {
                return response()->json(['message' => 'Assigned user must have the HOD role.'], 422);
            }
        }

        $department->update($request->only('name', 'code', 'hod_id'));

        return response()->json(['message' => 'Department updated successfully.']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DELETE  —  DELETE /api/departments/{department}  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function destroy(Department $department)
    {
        if ($department->courses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a department that still has courses. Reassign or delete them first.',
            ], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // COURSES  —  GET /api/departments/{department}/courses
    // ═══════════════════════════════════════════════════════════════════════════

    public function courses(Department $department)
    {
        $courses = Course::where('department_id', $department->id)
            ->with('lecturer:id,name')
            ->withCount('students')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'code'           => $c->code,
                'title'          => $c->title,
                'lecturer'       => $c->lecturer?->name,
                'students_count' => $c->students_count,
            ]);

        return response()->json($courses);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // STUDENTS  —  GET /api/departments/{department}/students
    // ═══════════════════════════════════════════════════════════════════════════

    public function students(Department $department)
    {
        $students = Student::where('department_id', $department->id)
            ->get(['id', 'name', 'matric', 'email', 'created_at'])
            ->map(fn($s) => [
                'studentId' => $s->id,
                'name'      => $s->name,
                'matric'    => $s->matric,
                'email'     => $s->email,
            ]);

        return response()->json($students);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LECTURERS  —  GET /api/departments/{department}/lecturers
    // Returns all lecturers who teach at least one course in the department
    // ═══════════════════════════════════════════════════════════════════════════

    public function lecturers(Department $department)
    {
        $lecturers = User::where('role', 'LECTURER')
            ->whereHas('courses', fn($q) => $q->where('department_id', $department->id))
            ->withCount(['courses as department_courses_count' => fn($q) =>
                $q->where('department_id', $department->id)
            ])
            ->get(['id', 'name', 'email'])
            ->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'courses_count'  => $u->department_courses_count,
            ]);

        return response()->json($lecturers);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // RESULTS SUMMARY  —  GET /api/departments/{department}/results/summary
    // HOD dashboard — pass/fail counts, average total per course
    // ═══════════════════════════════════════════════════════════════════════════

    public function resultsSummary(Department $department)
    {
        $courseIds = Course::where('department_id', $department->id)->pluck('id');

        $summary = Result::whereIn('course_id', $courseIds)
            ->with('course:id,code,title')
            ->selectRaw('
                course_id,
                COUNT(*)                                    as total_students,
                SUM(CASE WHEN total >= 40 THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN total <  40 THEN 1 ELSE 0 END) as failed,
                ROUND(AVG(total), 2)                         as average,
                SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "flagged"  THEN 1 ELSE 0 END) as flagged
            ')
            ->groupBy('course_id')
            ->get()
            ->map(fn($r) => [
                'course_id'      => $r->course_id,
                'course_code'    => $r->course?->code,
                'course_title'   => $r->course?->title,
                'total_students' => $r->total_students,
                'passed'         => $r->passed,
                'failed'         => $r->failed,
                'pass_rate'      => $r->total_students > 0
                    ? round(($r->passed / $r->total_students) * 100, 1)
                    : 0,
                'average'        => $r->average,
                'status_breakdown' => [
                    'pending'  => $r->pending,
                    'approved' => $r->approved,
                    'flagged'  => $r->flagged,
                ],
            ]);

        return response()->json($summary);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ASSIGN HOD  —  PATCH /api/departments/{department}/assign-hod  [RO only]
    // ═══════════════════════════════════════════════════════════════════════════

    public function assignHod(Request $request, Department $department)
    {
        $request->validate([
            'hod_id' => 'required|exists:users,id',
        ]);

        $hod = User::findOrFail($request->hod_id);

        if ($hod->role !== 'HOD') {
            return response()->json(['message' => 'User must have the HOD role.'], 422);
        }

        // Unassign from any other department first
        Department::where('hod_id', $hod->id)
            ->where('id', '!=', $department->id)
            ->update(['hod_id' => null]);

        $department->update(['hod_id' => $hod->id]);

        return response()->json([
            'message' => "{$hod->name} has been assigned as HOD of {$department->name}.",
        ]);
    }
}
