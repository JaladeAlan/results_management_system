<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegistrationOfficerController extends Controller
{
    // =========================================================================
    //  USER MANAGEMENT
    // =========================================================================

    /**
     * GET /api/ro/users
     * List all system users (lecturers, HODs, ROs).
     */
    public function getUsers(Request $request)
    {
        $users = User::query()
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        return response()->json($users);
    }

    /**
     * POST /api/ro/users
     * Create a new system user (Lecturer, HOD, or RO).
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:HOD,LECTURER,RO',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ], 201);
    }

    /**
     * PUT /api/ro/users/{userId}
     * Update a system user's details.
     */
    public function updateUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => "sometimes|email|unique:users,email,{$userId}",
            'password' => 'sometimes|string|min:8',
            'role'     => 'sometimes|in:HOD,LECTURER,RO',
        ]);

        $user->update(array_filter([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password ? Hash::make($request->password) : null,
            'role'     => $request->role,
        ]));

        return response()->json(['message' => 'User updated successfully.']);
    }

    /**
     * DELETE /api/ro/users/{userId}
     * Remove a system user.
     */
    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    // =========================================================================
    //  STUDENT MANAGEMENT
    // =========================================================================

    /**
     * GET /api/ro/students
     * List all registered students, optionally filtered by department.
     */
    public function getStudents(Request $request)
    {
        $students = Student::query()
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->with('department:id,name,code')
            ->get(['id', 'name', 'matric', 'email', 'department_id', 'created_at']);

        return response()->json($students);
    }

    /**
     * POST /api/ro/students
     * Register a new student.
     */
    public function createStudent(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'matric'        => 'required|string|unique:students,matric',
            'email'         => 'nullable|email|unique:students,email',
            'department_id' => 'required|exists:departments,id',
        ]);

        $student = Student::create($request->only('name', 'matric', 'email', 'department_id'));

        return response()->json([
            'message' => 'Student registered successfully.',
            'student' => ['id' => $student->id, 'name' => $student->name, 'matric' => $student->matric],
        ], 201);
    }

    /**
     * PUT /api/ro/students/{studentId}
     * Update a student's details.
     */
    public function updateStudent(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'matric'        => "sometimes|string|unique:students,matric,{$studentId}",
            'email'         => "sometimes|nullable|email|unique:students,email,{$studentId}",
            'department_id' => 'sometimes|exists:departments,id',
        ]);

        $student->update($request->only('name', 'matric', 'email', 'department_id'));

        return response()->json(['message' => 'Student updated successfully.']);
    }

    /**
     * DELETE /api/ro/students/{studentId}
     * Remove a student record.
     */
    public function deleteStudent($studentId)
    {
        $student = Student::findOrFail($studentId);
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    // =========================================================================
    //  COURSE ENROLMENT
    // =========================================================================

    /**
     * POST /api/ro/courses/{courseId}/enroll
     * Enroll one or more students in a course.
     */
    public function enrollStudents(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        // syncWithoutDetaching keeps existing enrolments intact
        $course->students()->syncWithoutDetaching($request->student_ids);

        return response()->json(['message' => 'Students enrolled successfully.']);
    }

    /**
     * DELETE /api/ro/courses/{courseId}/enroll/{studentId}
     * Remove a student from a course.
     */
    public function unenrollStudent($courseId, $studentId)
    {
        $course = Course::findOrFail($courseId);
        $course->students()->detach($studentId);

        return response()->json(['message' => 'Student removed from course successfully.']);
    }

    // =========================================================================
    //  DEPARTMENT & COURSE OVERVIEW
    // =========================================================================

    /**
     * GET /api/ro/departments
     * List all departments.
     */
    public function getDepartments()
    {
        $departments = Department::with('hod:id,name')->get(['id', 'name', 'code', 'hod_id']);

        return response()->json($departments);
    }

    /**
     * GET /api/ro/courses
     * List all courses with their lecturer and department.
     */
    public function getCourses()
    {
        $courses = Course::with(['lecturer:id,name', 'department:id,name,code'])
            ->get(['id', 'code', 'title', 'lecturer_id', 'department_id']);

        return response()->json($courses);
    }
}
