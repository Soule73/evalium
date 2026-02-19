import { useState, useCallback } from 'react';
import { route } from 'ziggy-js';
import { XMarkIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import { type AssignmentAttachment } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import Modal from '../ui/Modal/Modal';
import { Button } from '@evalium/ui';

const PREVIEWABLE_IMAGE_TYPES = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp',
  'image/svg+xml',
  'image/bmp',
];

const PREVIEWABLE_PDF_TYPES = ['application/pdf'];

type PreviewType = 'image' | 'pdf' | 'unsupported';

function getPreviewType(mimeType: string): PreviewType {
  if (PREVIEWABLE_IMAGE_TYPES.includes(mimeType)) return 'image';
  if (PREVIEWABLE_PDF_TYPES.includes(mimeType)) return 'pdf';
  return 'unsupported';
}

interface FilePreviewModalProps {
  attachment: AssignmentAttachment | null;
  isOpen: boolean;
  onClose: () => void;
}

/**
 * Modal for previewing file attachments.
 *
 * Supports inline preview for images and PDFs.
 * Falls back to a download prompt for unsupported file types.
 */
export function FilePreviewModal({ attachment, isOpen, onClose }: FilePreviewModalProps) {
  const { t } = useTranslations();
  const [imageError, setImageError] = useState(false);

  const handleClose = useCallback(() => {
    setImageError(false);
    onClose();
  }, [onClose]);

  if (!attachment) return null;

  const previewType = getPreviewType(attachment.mime_type);
  const previewUrl = route('attachments.preview', attachment.id);
  const downloadUrl = route('attachments.download', attachment.id);

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title={attachment.file_name}
      size={previewType === 'unsupported' ? 'md' : 'full'}
    >
      <div className="flex flex-col flex-1 min-h-0">
        {previewType === 'image' && !imageError && (
          <div className="flex-1 min-h-0 overflow-auto flex items-center justify-center">
            <img
              src={previewUrl}
              alt={attachment.file_name}
              className="max-w-full max-h-full object-contain rounded-lg"
              onError={() => setImageError(true)}
            />
          </div>
        )}

        {previewType === 'pdf' && (
          <div className="flex-1 min-h-0">
            <iframe
              src={previewUrl}
              title={attachment.file_name}
              className="w-full h-full rounded-lg border border-gray-200"
            />
          </div>
        )}

        {(previewType === 'unsupported' || imageError) && (
          <div className="flex-1 flex items-center justify-center">
            <div className="text-center">
              <XMarkIcon className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600 mb-2">
                {t('components.file_list.preview_not_available')}
              </p>
              <p className="text-sm text-gray-500">
                {attachment.mime_type}
              </p>
            </div>
          </div>
        )}

        <div className="flex justify-end gap-2 pt-4 mt-auto shrink-0 border-t border-gray-200">
          <a href={downloadUrl} download>
            <Button size="sm" color="primary" variant="outline">
              <ArrowDownTrayIcon className="w-4 h-4 mr-1" />
              {t('components.file_list.download')}
            </Button>
          </a>
          <Button size="sm" color="secondary" variant="outline" onClick={handleClose}>
            {t('components.file_list.close')}
          </Button>
        </div>
      </div>
    </Modal>
  );
}
