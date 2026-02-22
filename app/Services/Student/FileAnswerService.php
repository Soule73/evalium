<?php

namespace App\Services\Student;

use App\Models\Answer;
use App\Models\AssessmentAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Handles file upload/delete operations for QuestionType::File answers.
 *
 * Each file corresponds to one Answer row linked to a specific File question.
 * File size and extension constraints are read from config/assessment.php
 * instead of per-assessment database columns.
 */
class FileAnswerService
{
    /**
     * Store an uploaded file and persist the Answer record with file metadata.
     *
     * @return Answer The created or updated answer record
     */
    public function saveFileAnswer(
        AssessmentAssignment $assignment,
        int $questionId,
        UploadedFile $file
    ): Answer {
        $path = $file->store(
            "file-answers/{$assignment->assessment_id}/{$assignment->id}",
            'local'
        );

        return Answer::updateOrCreate(
            [
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $questionId,
            ],
            [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'answer_text' => null,
                'choice_id' => null,
            ]
        );
    }

    /**
     * Delete the stored file and the Answer record for a file question.
     */
    public function deleteFileAnswer(Answer $answer): bool
    {
        if ($answer->file_path) {
            Storage::disk('local')->delete($answer->file_path);
        }

        return $answer->delete();
    }

    /**
     * Return the system-level maximum file size in kilobytes.
     */
    public function getMaxFileSizeKb(): int
    {
        return (int) config('assessment.file_uploads.max_size_kb', 10240);
    }

    /**
     * Return the system-level list of allowed MIME extensions.
     *
     * @return array<string>
     */
    public function getAllowedExtensions(): array
    {
        return (array) config('assessment.file_uploads.allowed_extensions', []);
    }
}
