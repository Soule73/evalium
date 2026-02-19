<?php

namespace App\Http\Controllers;

use App\Models\AssignmentAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles file attachment download and preview for all authenticated users.
 *
 * Authorization: The student who owns the attachment, the teacher of the assessment,
 * or any admin can access the file.
 */
class AttachmentController extends Controller
{
    /**
     * Download an attachment file.
     */
    public function download(AssignmentAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeAccess($attachment);

        $path = Storage::disk('local')->path($attachment->file_path);

        abort_unless(file_exists($path), 404, __('messages.file_not_found'));

        return response()->download($path, $attachment->file_name);
    }

    /**
     * Stream an attachment file for inline preview.
     */
    public function preview(AssignmentAttachment $attachment): BinaryFileResponse
    {
        $this->authorizeAccess($attachment);

        $path = Storage::disk('local')->path($attachment->file_path);

        abort_unless(file_exists($path), 404, __('messages.file_not_found'));

        return response()->file($path, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    /**
     * Authorize access to the attachment.
     *
     * Allowed: attachment owner (student), assessment teacher, or admin.
     */
    private function authorizeAccess(AssignmentAttachment $attachment): void
    {
        $user = Auth::user();
        $assignment = $attachment->assignment()->with('enrollment', 'assessment')->first();

        abort_unless($assignment, 404);

        $isOwner = $assignment->enrollment?->student_id === $user->id;
        $isTeacher = $assignment->assessment?->teacher_id === $user->id;
        $isAdmin = $user->hasRole(['admin', 'super-admin']);

        abort_unless($isOwner || $isTeacher || $isAdmin, 403, __('messages.unauthorized'));
    }
}
