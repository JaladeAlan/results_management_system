<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'hod_id',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * The HOD assigned to this department.
     */
    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    /**
     * All courses belonging to this department.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * All students registered under this department.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}