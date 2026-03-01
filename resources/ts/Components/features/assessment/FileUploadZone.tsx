import { useCallback, useRef, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import { type Answer } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry } from '@/Components';
import { FileList } from '@/Components/shared/lists/FileList';

interface FileUploadZoneProps {
    assessmentId: number;
    questionId: number;
    fileAnswer?: Answer;
    onFileAnswerSaved: (answer: Answer) => void;
    onFileAnswerRemoved: (answerId: number) => void;
    disabled?: boolean;
}

/**
 * File upload zone for a single QuestionType::File question.
 *
 * Supports drag-and-drop and click-to-browse uploads.
 * Uploading a new file replaces the existing answer via updateOrCreate on the server.
 * File size and extension constraints are enforced server-side (config/assessment.php).
 */
export function FileUploadZone({
    assessmentId,
    questionId,
    fileAnswer,
    onFileAnswerSaved,
    onFileAnswerRemoved,
    disabled = false,
}: FileUploadZoneProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [deleting, setDeleting] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [dragOver, setDragOver] = useState(false);
    const { t } = useTranslations();

    const handleUpload = useCallback(
        async (file: File) => {
            setError(null);
            setUploading(true);

            const formData = new FormData();
            formData.append('file', file);
            formData.append('question_id', String(questionId));

            try {
                const response = await axios.post(
                    route('student.assessments.file-answers.upload', assessmentId),
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } },
                );
                onFileAnswerSaved(response.data.answer);
            } catch (err: unknown) {
                if (axios.isAxiosError(err) && err.response?.data?.message) {
                    setError(err.response.data.message);
                } else if (axios.isAxiosError(err) && err.response?.data?.errors?.file) {
                    setError(err.response.data.errors.file[0]);
                } else {
                    setError(t('student_assessment_pages.work.upload_error'));
                }
            } finally {
                setUploading(false);
            }
        },
        [assessmentId, questionId, onFileAnswerSaved, t],
    );

    const handleDelete = useCallback(
        async (answer: Answer) => {
            setError(null);
            setDeleting(answer.id);

            try {
                await axios.delete(
                    route('student.assessments.file-answers.delete', [assessmentId, answer.id]),
                );
                onFileAnswerRemoved(answer.id);
            } catch (err: unknown) {
                if (axios.isAxiosError(err) && err.response?.data?.message) {
                    setError(err.response.data.message);
                } else {
                    setError(t('student_assessment_pages.work.delete_error'));
                }
            } finally {
                setDeleting(null);
            }
        },
        [assessmentId, onFileAnswerRemoved, t],
    );

    const handleFileChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0];
            if (file) {
                handleUpload(file);
            }
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        },
        [handleUpload],
    );

    const handleDrop = useCallback(
        (e: React.DragEvent<HTMLDivElement>) => {
            e.preventDefault();
            setDragOver(false);
            if (disabled) return;

            const file = e.dataTransfer.files?.[0];
            if (file) {
                handleUpload(file);
            }
        },
        [disabled, handleUpload],
    );

    const handleDragOver = useCallback(
        (e: React.DragEvent<HTMLDivElement>) => {
            e.preventDefault();
            if (!disabled) {
                setDragOver(true);
            }
        },
        [disabled],
    );

    const handleDragLeave = useCallback(() => {
        setDragOver(false);
    }, []);

    const currentAnswers: Answer[] = fileAnswer ? [fileAnswer] : [];

    return (
        <div className="space-y-4">
            {error && (
                <AlertEntry type="error" title={t('student_assessment_pages.work.upload_error')}>
                    <p>{error}</p>
                </AlertEntry>
            )}

            {!disabled && (
                <div
                    onDrop={handleDrop}
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    className={`border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer ${
                        dragOver
                            ? 'border-indigo-500 bg-indigo-50'
                            : 'border-gray-300 hover:border-gray-400 bg-gray-50'
                    }`}
                    onClick={() => fileInputRef.current?.click()}
                >
                    <ArrowUpTrayIcon className="mx-auto h-10 w-10 text-gray-400" />
                    <p className="mt-2 text-sm font-medium text-gray-700">
                        {fileAnswer
                            ? t('student_assessment_pages.work.replace_file')
                            : t('student_assessment_pages.work.drop_files_here')}
                    </p>
                    <p className="mt-1 text-xs text-gray-500">
                        {t('student_assessment_pages.work.browse_files')}
                    </p>

                    <input
                        ref={fileInputRef}
                        type="file"
                        className="hidden"
                        onChange={handleFileChange}
                        disabled={uploading}
                    />
                </div>
            )}

            {uploading && (
                <div className="flex items-center justify-center py-3">
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600 mr-2" />
                    <span className="text-sm text-gray-600">
                        {t('student_assessment_pages.work.uploading')}
                    </span>
                </div>
            )}

            {currentAnswers.length > 0 && (
                <FileList
                    attachments={currentAnswers}
                    onDelete={disabled ? undefined : (answer) => handleDelete(answer)}
                    deleteLoading={deleting}
                    readOnly={disabled}
                />
            )}
        </div>
    );
}
