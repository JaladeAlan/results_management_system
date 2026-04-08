<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HodController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\RegistrationOfficerController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ── Authenticated (JWT required) ──────────────────────────────────────────────
Route::middleware('jwt.auth')->group(function () {

    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me',       [AuthController::class, 'me']);

       // ── Departments ────────────────────────
    Route::get('/departments',                              [DepartmentController::class, 'index']);
    Route::get('/departments/{department}',                 [DepartmentController::class, 'show']);
    Route::get('/departments/{department}/courses',         [DepartmentController::class, 'courses']);
    Route::get('/departments/{department}/students',        [DepartmentController::class, 'students']);
    Route::get('/departments/{department}/lecturers',       [DepartmentController::class, 'lecturers']);
    Route::get('/departments/{department}/results/summary', [DepartmentController::class, 'resultsSummary']);

    // ── Shared: Lecturer + HOD ────────────────────────────────────────────────
    Route::middleware('role:LECTURER,HOD')->group(function () {
        Route::get('/courses',                             [CourseController::class, 'index']);
        Route::get('/courses/{course}',                    [CourseController::class, 'show']);
        Route::get('/lecturers/{lecturerId}/courses',      [LecturerController::class, 'getCourses']);
        Route::get('/courses/{courseId}/students',         [LecturerController::class, 'getCourseStudents']);
        Route::post('/courses/{courseId}/results',         [LecturerController::class, 'uploadResults']);

        // Both roles need to view and download results
        Route::get('/courses/{courseId}/results',          [HodController::class, 'getCourseResults']);
        Route::get('/courses/{courseId}/results/download', [LecturerController::class, 'downloadResults']);
    });

    // ── HOD only ──────────────────────────────────────────────────────────────
    Route::middleware('role:HOD')->group(function () {
        Route::get('/hod/{departmentId}/courses/pending',  [HodController::class, 'getPendingCourses']);
        Route::get('/hod/{departmentId}/courses/approved', [HodController::class, 'getApprovedCourses']);
        Route::post('/courses/{courseId}/results/approve', [HodController::class, 'approveResults']);
        Route::post('/courses/{courseId}/results/flag',    [HodController::class, 'flagResults']);
    });

    // ── Registration Officer ───────────────────────────────────────────────────
    Route::middleware('role:RO')->prefix('ro')->group(function () {

        Route::get('/users',             [RegistrationOfficerController::class, 'getUsers']);
        Route::post('/users',            [RegistrationOfficerController::class, 'createUser']);
        Route::put('/users/{userId}',    [RegistrationOfficerController::class, 'updateUser']);
        Route::delete('/users/{userId}', [RegistrationOfficerController::class, 'deleteUser']);

        Route::get('/students',                [RegistrationOfficerController::class, 'getStudents']);
        Route::post('/students',               [RegistrationOfficerController::class, 'createStudent']);
        Route::put('/students/{studentId}',    [RegistrationOfficerController::class, 'updateStudent']);
        Route::delete('/students/{studentId}', [RegistrationOfficerController::class, 'deleteStudent']);

        Route::post('/courses/{courseId}/enroll',               [RegistrationOfficerController::class, 'enrollStudents']);
        Route::delete('/courses/{courseId}/enroll/{studentId}', [RegistrationOfficerController::class, 'unenrollStudent']);

        Route::get('/departments', [RegistrationOfficerController::class, 'getDepartments']);
        Route::get('/courses',     [RegistrationOfficerController::class, 'getCourses']);

        Route::post('/courses',                           [CourseController::class, 'store']);
        Route::put('/courses/{course}',                   [CourseController::class, 'update']);
        Route::delete('/courses/{course}',                [CourseController::class, 'destroy']);
        Route::patch('/courses/{course}/assign-lecturer', [CourseController::class, 'assignLecturer']);

        Route::post('/departments/create',                       [DepartmentController::class, 'store']);
        Route::put('/departments/{department}',                  [DepartmentController::class, 'update']);
        Route::delete('/departments/{department}',               [DepartmentController::class, 'destroy']);
        Route::patch('/departments/{department}/assign-hod',     [DepartmentController::class, 'assignHod']);
    });
});