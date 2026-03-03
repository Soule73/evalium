<?php

namespace App\Models;

use App\Enums\GradeReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grade report (bulletin) for a student in a given semester or annual period.
 * Stores computed snapshot data, remarks, ranking and generated PDF path.
 */
class GradeReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'semester_id',
        'academic_year_id',
        'data',
        'remarks',
        'general_remark',
        'rank',
        'average',
        'status',
        'validated_by',
        'validated_at',
        'file_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'remarks' => 'array',
            'average' => 'decimal:2',
            'status' => GradeReportStatus::class,
            'validated_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
