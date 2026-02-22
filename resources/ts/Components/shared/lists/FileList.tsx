import { useState, useMemo, useCallback, type ReactNode } from 'react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type Answer } from '@/types';
import { Badge } from '@evalium/ui';
import { formatFileSize } from '@/utils';
import { useTranslations } from '@/hooks';
import { FilePreviewModal } from '../FilePreviewModal';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

type TranslateFn = (key: string) => string;

const IMAGE_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    'image/bmp',
];
const PDF_TYPES = ['application/pdf'];
const DOCUMENT_TYPES = [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv',
];
const ARCHIVE_TYPES = [
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
];

function getFileTypeBadge(mimeType: string, t: TranslateFn): ReactNode {
    if (IMAGE_TYPES.includes(mimeType)) {
        return <Badge label={t('components.file_list.type_image')} type="info" size="sm" />;
    }
    if (PDF_TYPES.includes(mimeType)) {
        return <Badge label={t('components.file_list.type_pdf')} type="warning" size="sm" />;
    }
    if (DOCUMENT_TYPES.includes(mimeType)) {
        return <Badge label={t('components.file_list.type_document')} type="success" size="sm" />;
    }
    if (ARCHIVE_TYPES.includes(mimeType)) {
        return <Badge label={t('components.file_list.type_archive')} type="gray" size="sm" />;
    }
    return <Badge label={t('components.file_list.type_other')} type="gray" size="sm" />;
}

function isPreviewable(mimeType: string): boolean {
    return IMAGE_TYPES.includes(mimeType) || PDF_TYPES.includes(mimeType);
}

function buildColumns(t: TranslateFn): EntityListConfig<Answer>['columns'] {
    return [
        {
            key: 'file_name',
            labelKey: 'components.file_list.file_name',
            render: (attachment) => (
                <div className="flex items-center gap-3">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate max-w-xs">
                            {attachment.file_name}
                        </p>
                        <p className="text-xs text-gray-500">
                            {formatFileSize(attachment.file_size ?? 0)}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            key: 'mime_type',
            labelKey: 'components.file_list.file_type',
            render: (attachment) => getFileTypeBadge(attachment.mime_type ?? '', t),
        },
    ];
}

interface FileListProps {
    attachments: Answer[];
    showPagination?: boolean;
    onDelete?: (answer: Answer) => void;
    deleteLoading?: number | null;
    readOnly?: boolean;
}

/**
 * File attachment list component using BaseEntityList.
 *
 * Displays attachments in a DataTable with preview and download actions.
 * Supports image and PDF inline preview via FilePreviewModal.
 */
export function FileList({
    attachments,
    showPagination = false,
    onDelete,
    deleteLoading = null,
    readOnly = false,
}: FileListProps) {
    const { t } = useTranslations();
    const [previewAttachment, setPreviewAttachment] = useState<Answer | null>(null);

    const handlePreview = useCallback((answer: Answer) => {
        if (isPreviewable(answer.mime_type ?? '')) {
            setPreviewAttachment(answer);
        } else {
            window.open(route('file-answers.download', answer.id), '_blank');
        }
    }, []);

    const handleClosePreview = useCallback(() => {
        setPreviewAttachment(null);
    }, []);

    const data: PaginationType<Answer> = useMemo(
        () => ({
            data: attachments,
            current_page: 1,
            first_page_url: '',
            from: attachments.length > 0 ? 1 : null,
            last_page: 1,
            last_page_url: '',
            links: [],
            next_page_url: null,
            path: '',
            per_page: attachments.length || 10,
            prev_page_url: null,
            to: attachments.length > 0 ? attachments.length : null,
            total: attachments.length,
        }),
        [attachments],
    );

    const config: EntityListConfig<Answer> = useMemo(() => {
        const columns = buildColumns(t);

        const actions: EntityListConfig<Answer>['actions'] = [
            {
                labelKey: 'components.file_list.preview',
                onClick: (answer) => handlePreview(answer),
                color: 'secondary',
                variant: 'outline',
                conditional: (item) => isPreviewable(item.mime_type ?? ''),
            },
            {
                labelKey: 'components.file_list.download',
                onClick: (answer) => {
                    window.open(route('file-answers.download', answer.id), '_blank');
                },
                color: 'primary',
                variant: 'outline',
            },
        ];

        if (onDelete && !readOnly) {
            actions.push({
                labelKey: 'commons/ui.delete',
                onClick: (answer) => onDelete(answer),
                color: 'danger',
                variant: 'outline',
                conditional: (item) => deleteLoading !== item.id,
            });
        }

        return {
            entity: 'file',
            columns,
            actions,
        };
    }, [t, handlePreview, onDelete, readOnly, deleteLoading]);

    return (
        <>
            <BaseEntityList
                data={data}
                config={config}
                showSearch={false}
                showPagination={showPagination}
                emptyMessage={t('components.file_list.no_files')}
            />
            <FilePreviewModal
                attachment={previewAttachment}
                isOpen={!!previewAttachment}
                onClose={handleClosePreview}
            />
        </>
    );
}
