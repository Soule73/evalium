import { useEffect } from 'react';
import { type FlashMessageObject, type FlashMessages } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useToast } from './useToast';

interface FlashToastHandlerProps {
    flash: FlashMessages;
}

const displayedIds = new Set<string>();

const FlashToastHandler: React.FC<FlashToastHandlerProps> = ({ flash }) => {
    const { success, error, warning, info } = useToast();
    const { t } = useTranslations();

    const successTitle = t('components.toast.success');
    const errorTitle = t('components.toast.error');
    const warningTitle = t('components.toast.warning');
    const infoTitle = t('components.toast.info');
    const closeLabel = t('components.toast.close');

    useEffect(() => {
        const showToast = (
            type: 'success' | 'error' | 'warning' | 'info' | 'message',
            data?: FlashMessageObject,
        ) => {
            if (!data || !data.id || !data.message) return;

            if (displayedIds.has(data.id)) return;

            displayedIds.add(data.id);

            switch (type) {
                case 'success':
                    success(data.message, { title: successTitle, duration: 4000, closeLabel });
                    break;
                case 'error':
                    error(data.message, { title: errorTitle, autoClose: false, closeLabel });
                    break;
                case 'warning':
                    warning(data.message, { title: warningTitle, duration: 6000, closeLabel });
                    break;
                case 'info':
                    info(data.message, { title: infoTitle, duration: 5000, closeLabel });
                    break;
                case 'message':
                    info(data.message, { duration: 5000, closeLabel });
                    break;
            }
        };

        showToast('success', flash.success);
        showToast('error', flash.error);
        showToast('warning', flash.warning);
        showToast('info', flash.info);
    }, [
        flash,
        success,
        error,
        warning,
        info,
        successTitle,
        errorTitle,
        warningTitle,
        infoTitle,
        closeLabel,
    ]);

    return null;
};

export { FlashToastHandler };
