<?php

namespace App\Services\Student;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssignmentAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file attachment operations for homework assessments.
 */
class AttachmentService
{
    /**
     * Upload a file attachment for a homework assignment.
     *
     * @return AssignmentAttachment The created attachment record
     */
    public function uploadAttachment(
        AssessmentAssignment $assignment,
        Assessment $assessment,
        UploadedFile $file
    ): AssignmentAttachment {
        $path = $file->store(
            "attachments/{$assessment->id}/{$assignment->id}",
            'local'
        );

        return $assignment->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now(),
        ]);
    }

    /**
     * Delete a file attachment and remove from storage.
     */
    public function deleteAttachment(AssignmentAttachment $attachment): bool
    {
        Storage::disk('local')->delete($attachment->file_path);

        return $attachment->delete();
    }

    /**
     * Check if the assignment has reached the maximum number of file uploads.
     *
     * @return bool True if no more files can be uploaded
     */
    public function hasReachedFileLimit(AssessmentAssignment $assignment, Assessment $assessment): bool
    {
        if (! $assessment->max_files || $assessment->max_files <= 0) {
            return false;
        }

        return $assignment->attachments()->count() >= $assessment->max_files;
    }

    /**
     * Get all attachments for an assignment.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AssignmentAttachment>
     */
    public function getAttachments(AssessmentAssignment $assignment)
    {
        return $assignment->attachments()->orderBy('uploaded_at', 'desc')->get();
    }
}
