<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── JWT interface ─────────────────────────────────────────────────────

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array of arbitrary claims added to the JWT payload.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'name' => $this->name,
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * Courses this user lectures (role: LECTURER).
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'lecturer_id');
    }

    /**
     * Department this user heads (role: HOD).
     */
    public function department()
    {
        return $this->hasOne(Department::class, 'hod_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isHod(): bool
    {
        return $this->role === 'HOD';
    }

    public function isLecturer(): bool
    {
        return $this->role === 'LECTURER';
    }

    public function isRo(): bool
    {
        return $this->role === 'RO';
    }
}