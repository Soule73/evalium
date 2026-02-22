<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles download and inline preview of files stored as Answer records
 * for QuestionType::File questions.
 *
 * Authorization: the student who submitted the answer, the teacher of the
 * assessment, or any admin can access the file.
 */
class FileAnswerController extends Controller
{
    /**
     * Download a file answer as an attachment.
     */
    public function download(Answer $answer): BinaryFileResponse
    {
        $this->authorizeAccess($answer);

        $path = Storage::disk('local')->path($answer->file_path);

        abort_unless(file_exists($path), 404, __('messages.file_not_found'));

        return response()->download($path, $answer->file_name);
    }

    /**
     * Stream a file answer inline for preview.
     */
    public function preview(Answer $answer): BinaryFileResponse
    {
        $this->authorizeAccess($answer);

        $path = Storage::disk('local')->path($answer->file_path);

        abort_unless(file_exists($path), 404, __('messages.file_not_found'));

        return response()->file($path, [
            'Content-Type' => $answer->mime_type,
            'Content-Disposition' => 'inline; filename="'.$answer->file_name.'"',
        ]);
    }

    /**
     * Ensure the authenticated user is allowed to access the given file answer.
     *
     * Allowed: the student who owns the assignment, the teacher of the
     * assessment, or an admin.
     */
    private function authorizeAccess(Answer $answer): void
    {
        abort_unless($answer->file_path, 404);

        $user = Auth::user();
        $assignment = $answer->assessmentAssignment()->with('enrollment', 'assessment')->first();

        abort_unless($assignment, 404);

        $isOwner = $assignment->enrollment?->student_id === $user->id;
        $isTeacher = $assignment->assessment?->teacher_id === $user->id;
        $isAdmin = $user->hasRole(['admin', 'super_admin']);

        abort_unless($isOwner || $isTeacher || $isAdmin, 403, __('messages.unauthorized'));
    }
}
