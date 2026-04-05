<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'ca1',
        'ca2',
        'exam',
        'total',
        'status',
        'flag_description',
    ];

    protected $casts = [
        'ca1'   => 'integer',
        'ca2'   => 'integer',
        'exam'  => 'integer',
        'total' => 'integer',
    ];

    /**
     * Combined CA score (ca1 + ca2), max 40.
     */
    public function getTotalCaAttribute(): int
    {
        return $this->ca1 + $this->ca2;
    }

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * The course this result belongs to.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The student this result belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Derive a letter grade from the total score.
     */
    public function getGradeAttribute(): string
    {
        return match (true) {
            $this->total >= 70 => 'A',
            $this->total >= 60 => 'B',
            $this->total >= 50 => 'C',
            $this->total >= 45 => 'D',
            $this->total >= 40 => 'E',
            default            => 'F',
        };
    }

    /**
     * Determine if the student passed (total >= 40).
     */
    public function getPassedAttribute(): bool
    {
        return $this->total >= 40;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isFlagged(): bool
    {
        return $this->status === 'flagged';
    }
}