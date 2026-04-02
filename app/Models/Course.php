<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'lecturer_id',
        'department_id',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * The lecturer assigned to this course.
     */
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * The department this course belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Students enrolled in this course (via pivot table).
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'course_student')
                    ->withTimestamps();
    }

    /**
     * Results submitted for this course.
     */
    public function results()
    {
        return $this->hasMany(Result::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Check if all results for this course are approved.
     */
    public function isFullyApproved(): bool
    {
        return $this->results()->where('status', '!=', 'approved')->doesntExist();
    }

    /**
     * Check if this course has any pending results.
     */
    public function hasPendingResults(): bool
    {
        return $this->results()->where('status', 'pending')->exists();
    }
}