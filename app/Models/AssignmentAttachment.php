<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File attachment uploaded by a student for a homework assessment.
 */
class AssignmentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_assignment_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the assignment this attachment belongs to.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssessmentAssignment::class, 'assessment_assignment_id');
    }
}
