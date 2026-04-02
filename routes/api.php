<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HodController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\RegistrationOfficerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Result Management System (JWT Auth)
|--------------------------------------------------------------------------
*/

// ── Public ───────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ── Authenticated (JWT required) ──────────────────────────────────────────
Route::middleware('jwt.auth')->group(function () {

    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me',       [AuthController::class, 'me']);

    // ── Lecturer (HOD inherits these privileges) ──────────────────────────
    Route::middleware('role:LECTURER,HOD')->group(function () {
        Route::get('/lecturers/{lecturerId}/courses',      [LecturerController::class, 'getCourses']);
        Route::get('/courses/{courseId}/students',         [LecturerController::class, 'getCourseStudents']);
        Route::post('/courses/{courseId}/results',         [LecturerController::class, 'uploadResults']);
        Route::get('/courses/{courseId}/results/download', [LecturerController::class, 'downloadResults']);
    });

    // ── HOD ───────────────────────────────────────────────────────────────
    Route::middleware('role:HOD')->group(function () {
        Route::get('/hod/{departmentId}/courses/pending',  [HodController::class, 'getPendingCourses']);
        Route::get('/hod/{departmentId}/courses/approved', [HodController::class, 'getApprovedCourses']);
        Route::get('/courses/{courseId}/results',          [HodController::class, 'getCourseResults']);
        Route::post('/courses/{courseId}/results/approve', [HodController::class, 'approveResults']);
        Route::post('/courses/{courseId}/results/flag',    [HodController::class, 'flagResults']);
    });

    // ── Registration Officer ──────────────────────────────────────────────
    Route::middleware('role:RO')->prefix('ro')->group(function () {

        // User management
        Route::get('/users',             [RegistrationOfficerController::class, 'getUsers']);
        Route::post('/users',            [RegistrationOfficerController::class, 'createUser']);
        Route::put('/users/{userId}',    [RegistrationOfficerController::class, 'updateUser']);
        Route::delete('/users/{userId}', [RegistrationOfficerController::class, 'deleteUser']);

        // Student management
        Route::get('/students',                [RegistrationOfficerController::class, 'getStudents']);
        Route::post('/students',               [RegistrationOfficerController::class, 'createStudent']);
        Route::put('/students/{studentId}',    [RegistrationOfficerController::class, 'updateStudent']);
        Route::delete('/students/{studentId}', [RegistrationOfficerController::class, 'deleteStudent']);

        // Course enrolment
        Route::post('/courses/{courseId}/enroll',                  [RegistrationOfficerController::class, 'enrollStudents']);
        Route::delete('/courses/{courseId}/enroll/{studentId}',    [RegistrationOfficerController::class, 'unenrollStudent']);

        // Overview
        Route::get('/departments', [RegistrationOfficerController::class, 'getDepartments']);
        Route::get('/courses',     [RegistrationOfficerController::class, 'getCourses']);
    });
});