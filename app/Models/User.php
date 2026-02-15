<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * Represents a user of the application.
 *
 * @property int $id The unique identifier for the user.
 * @property string $name The name of the user.
 * @property string $email The email address of the user.
 * @property \Illuminate\Support\Carbon|null $email_verified_at The date and time when the user's email was verified.
 * @property string $password The hashed password of the user.
 * @property string|null $remember_token The token used to remember the user.
 * @property \Illuminate\Support\Carbon|null $created_at The date and time when the user was created.
 * @property \Illuminate\Support\Carbon|null $updated_at The date and time when the user was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Question newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question query()
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Assessment> $assessments The assessments created by the user (if teacher).
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,
        HasRoles,
        Notifiable,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'is_active',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the assessments associated with the user (for teachers).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Assessment>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'teacher_id');
    }

    /**
     * Get the assessment assignments for the user (student) through enrollments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\AssessmentAssignment, \App\Models\Enrollment>
     */
    public function assessmentAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            AssessmentAssignment::class,
            Enrollment::class,
            'student_id',
            'enrollment_id',
        );
    }

    /**
     * Get the enrollments associated with the user (for students).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Enrollment>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Get the classes associated with the user (for students via enrollments).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\ClassModel>
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'enrollments', 'student_id', 'class_id')
            ->withPivot(['enrolled_at', 'withdrawn_at', 'status'])
            ->withTimestamps();
    }

    /**
     * Get the class subjects where the user is teaching (for teachers).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ClassSubject>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'teacher_id');
    }
}
