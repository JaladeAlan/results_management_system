<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Result;
use Illuminate\Http\Request;

class HodController extends Controller
{
    /**
     * GET /api/hod/{departmentId}/courses/pending
     */
    public function getPendingCourses($departmentId)
    {
        $courses = Course::where('department_id', $departmentId)
            ->whereHas('results', fn($q) => $q->where('status', 'pending'))
            ->with('lecturer:id,name')
            ->get()
            ->map(fn($course) => [
                'id'       => $course->id,
                'code'     => $course->code,
                'title'    => $course->title,
                'lecturer' => $course->lecturer?->name,
                'status'   => 'pending',
            ]);

        return response()->json($courses);
    }

    /**
     * GET /api/hod/{departmentId}/courses/approved
     */
    public function getApprovedCourses($departmentId)
    {
        $courses = Course::where('department_id', $departmentId)
            ->whereHas('results', fn($q) => $q->where('status', 'approved'))
            ->with('lecturer:id,name')
            ->get()
            ->map(fn($course) => [
                'id'       => $course->id,
                'code'     => $course->code,
                'title'    => $course->title,
                'lecturer' => $course->lecturer?->name,
                'status'   => 'approved',
            ]);

        return response()->json($courses);
    }

    /**
     * GET /api/courses/{courseId}/results
     */
    public function getCourseResults($courseId)
    {
        Course::findOrFail($courseId);

        $results = Result::where('course_id', $courseId)
            ->with('student:id,name,matric')
            ->get()
            ->map(fn($result) => [
                'matric'   => $result->student->matric,
                'name'     => $result->student->name,
                'ca1'      => $result->ca1,
                'ca2'      => $result->ca2,
                'total_ca' => $result->total_ca,
                'exam'     => $result->exam,
                'total'    => $result->total,
                'grade'    => $result->grade,
            ]);

        return response()->json($results);
    }

    /**
     * POST /api/courses/{courseId}/results/approve
     */
    public function approveResults($courseId)
    {
        Course::findOrFail($courseId);

        Result::where('course_id', $courseId)
              ->where('status', 'pending')
              ->update(['status' => 'approved', 'flag_description' => null]);

        return response()->json(['message' => 'Results approved successfully.']);
    }

    /**
     * POST /api/courses/{courseId}/results/flag
     */
    public function flagResults(Request $request, $courseId)
    {
        Course::findOrFail($courseId);

        $request->validate([
            'description' => 'required|string|min:5',
        ]);

        Result::where('course_id', $courseId)
              ->where('status', 'pending')
              ->update([
                  'status'           => 'flagged',
                  'flag_description' => $request->description,
              ]);

        return response()->json(['message' => 'Issue flagged successfully.']);
    }
}