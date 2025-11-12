import { useState, useEffect, useCallback } from 'react';

interface AutoSaveConfig {
    interval?: number; // Auto-save interval in milliseconds
    debounceTime?: number; // Debounce time for changes
    maxRetries?: number; // Maximum retry attempts
    onSave?: (data: any) => Promise<void>;
    onError?: (error: Error) => void;
}

interface UseAutoSaveReturn<T> {
    data: T;
    isModified: boolean;
    isSaving: boolean;
    lastSaved: Date | null;
    saveCount: number;
    updateData: (updates: Partial<T>) => void;
    saveNow: () => Promise<void>;
    resetModified: () => void;
}

export function useAutoSave<T extends Record<string, any>>(
    initialData: T,
    config: AutoSaveConfig = {}
): UseAutoSaveReturn<T> {
    const {
        interval = 30000, // 30 seconds
        debounceTime = 2000, // 2 seconds
        maxRetries = 3,
        onSave,
        onError,
    } = config;

    const [data, setData] = useState<T>(initialData);
    const [isModified, setIsModified] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [lastSaved, setLastSaved] = useState<Date | null>(null);
    const [saveCount, setSaveCount] = useState(0);

    const updateData = useCallback((updates: Partial<T>) => {
        setData(prev => ({
            ...prev,
            ...updates,
        }));
        setIsModified(true);
    }, []);

    const saveNow = useCallback(async () => {
        if (!onSave || isSaving) return;

        setIsSaving(true);
        let retries = 0;

        while (retries < maxRetries) {
            try {
                await onSave(data);
                setIsModified(false);
                setLastSaved(new Date());
                setSaveCount(prev => prev + 1);
                break;
            } catch (error) {
                retries++;
                if (retries >= maxRetries) {
                    onError?.(error as Error);
                    break;
                }
                // Wait before retry (exponential backoff)
                await new Promise(resolve => setTimeout(resolve, Math.pow(2, retries) * 1000));
            }
        }

        setIsSaving(false);
    }, [data, isSaving, maxRetries, onSave, onError]);

    const resetModified = useCallback(() => {
        setIsModified(false);
    }, []);

    // Auto-save effect
    useEffect(() => {
        if (!isModified || !onSave) return;

        const debounceTimer = setTimeout(() => {
            saveNow();
        }, debounceTime);

        return () => clearTimeout(debounceTimer);
    }, [data, isModified, debounceTime, saveNow, onSave]);

    // Periodic save effect
    useEffect(() => {
        if (!onSave) return;

        const intervalTimer = setInterval(() => {
            if (isModified) {
                saveNow();
            }
        }, interval);

        return () => clearInterval(intervalTimer);
    }, [interval, isModified, saveNow, onSave]);

    // Save on page unload
    useEffect(() => {
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (isModified) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        };

        const handleUnload = () => {
            if (isModified && onSave) {
                // Try to save with sendBeacon if available
                if (navigator.sendBeacon) {
                    const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
                    navigator.sendBeacon('/api/emergency-save', blob);
                }
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        window.addEventListener('unload', handleUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            window.removeEventListener('unload', handleUnload);
        };
    }, [isModified, data, onSave]);

    return {
        data,
        isModified,
        isSaving,
        lastSaved,
        saveCount,
        updateData,
        saveNow,
        resetModified,
    };
}