import { useCallback, useRef, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import { type AssignmentAttachment } from '@/types';
import { formatFileSize } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry } from '@/Components';
import { FileList } from '@/Components/shared/lists/FileList';

interface FileUploadZoneProps {
    assessmentId: number;
    attachments: AssignmentAttachment[];
    maxFiles: number;
    maxFileSize: number;
    allowedExtensions: string | null;
    onAttachmentAdded: (attachment: AssignmentAttachment) => void;
    onAttachmentRemoved: (attachmentId: number) => void;
    disabled?: boolean;
}

/**
 * File upload zone component for homework assessments.
 * Supports drag-and-drop and click-to-browse file uploads.
 */
export function FileUploadZone({
    assessmentId,
    attachments,
    maxFiles,
    maxFileSize,
    allowedExtensions,
    onAttachmentAdded,
    onAttachmentRemoved,
    disabled = false,
}: FileUploadZoneProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [deleting, setDeleting] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [dragOver, setDragOver] = useState(false);
    const { t } = useTranslations();

    const extensionsArray = allowedExtensions
        ? allowedExtensions.split(',').map((ext) => ext.trim())
        : [];

    const acceptString =
        extensionsArray.length > 0 ? extensionsArray.map((ext) => `.${ext}`).join(',') : undefined;

    const hasReachedLimit = attachments.length >= maxFiles;

    const handleUpload = useCallback(
        async (file: File) => {
            setError(null);
            setUploading(true);

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await axios.post(
                    route('student.assessments.attachments.upload', assessmentId),
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } },
                );
                onAttachmentAdded(response.data.attachment);
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
        [assessmentId, onAttachmentAdded, t],
    );

    const handleDelete = useCallback(
        async (attachment: AssignmentAttachment) => {
            setError(null);
            setDeleting(attachment.id);

            try {
                await axios.delete(
                    route('student.assessments.attachments.delete', [assessmentId, attachment.id]),
                );
                onAttachmentRemoved(attachment.id);
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
        [assessmentId, onAttachmentRemoved, t],
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
            if (disabled || hasReachedLimit) return;

            const file = e.dataTransfer.files?.[0];
            if (file) {
                handleUpload(file);
            }
        },
        [disabled, hasReachedLimit, handleUpload],
    );

    const handleDragOver = useCallback(
        (e: React.DragEvent<HTMLDivElement>) => {
            e.preventDefault();
            if (!disabled && !hasReachedLimit) {
                setDragOver(true);
            }
        },
        [disabled, hasReachedLimit],
    );

    const handleDragLeave = useCallback(() => {
        setDragOver(false);
    }, []);

    return (
        <div className="space-y-4">
            <h3 className="text-lg font-medium text-gray-900">
                {t('student_assessment_pages.work.file_upload_title')}
            </h3>

            <p className="text-sm text-gray-600">
                {t('student_assessment_pages.work.file_upload_description')}
            </p>

            {error && (
                <AlertEntry type="error" title={t('student_assessment_pages.work.upload_error')}>
                    <p>{error}</p>
                </AlertEntry>
            )}

            {!hasReachedLimit && !disabled && (
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
                        {t('student_assessment_pages.work.drop_files_here')}
                    </p>
                    <p className="mt-1 text-xs text-gray-500">
                        {t('student_assessment_pages.work.browse_files')}
                    </p>

                    <input
                        ref={fileInputRef}
                        type="file"
                        className="hidden"
                        accept={acceptString}
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

            <div className="text-xs text-gray-500 space-y-1">
                <p>
                    {t('student_assessment_pages.work.file_limit', {
                        current: String(attachments.length),
                        max: String(maxFiles),
                    })}
                </p>
                <p>
                    {t('student_assessment_pages.work.max_file_size', {
                        size: formatFileSize(maxFileSize * 1024),
                    })}
                </p>
                {extensionsArray.length > 0 && (
                    <p>
                        {t('student_assessment_pages.work.allowed_types', {
                            types: extensionsArray.join(', '),
                        })}
                    </p>
                )}
            </div>

            {attachments.length > 0 && (
                <FileList
                    attachments={attachments}
                    onDelete={disabled ? undefined : (attachment) => handleDelete(attachment)}
                    deleteLoading={deleting}
                    readOnly={disabled}
                />
            )}
        </div>
    );
}
