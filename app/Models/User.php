<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Exam> $exams The exams created by the user (if teacher).
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ExamAssignment> $examAssignments The exam assignments for the user (if student).
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,
        Notifiable,
        HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }

    /**
     * Get the exams associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Exam>
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'teacher_id');
    }

    /**
     * Get the exam assignments associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ExamAssignment>
     */
    public function examAssignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class, 'student_id');
    }

    /**
     * Get the groups associated with the user (student).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Group>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student', 'student_id', 'group_id')
            ->withPivot(['enrolled_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the current active group for the student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Group>
     */
    public function activeGroup(): BelongsToMany
    {
        return $this->groups()->wherePivot('is_active', true);
    }

    /**
     * Get all active groups for the student (alias for better readability)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Group>
     */
    public function activeGroups(): BelongsToMany
    {
        return $this->activeGroup();
    }

    /**
     * Get the current active group (single instance)
     */
    public function getCurrentGroup(): ?Group
    {
        return $this->activeGroup()->first();
    }
}
