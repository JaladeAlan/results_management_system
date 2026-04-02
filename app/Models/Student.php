<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'matric',
        'email',
        'department_id',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * The department this student belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Courses this student is enrolled in (via pivot table).
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student')
                    ->withTimestamps();
    }

    /**
     * All results across all courses for this student.
     */
    public function results()
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Result for a specific course.
     */
    public function resultForCourse(int $courseId): ?Result
    {
        return $this->results()->where('course_id', $courseId)->first();
    }
}