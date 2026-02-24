import { Button } from '@/Components';

type SavingStatus = 'idle' | 'saving' | 'saved' | 'error';

interface Props {
    savingStatus: SavingStatus;
    isSubmitting: boolean;
    processing: boolean;
    saveButtonLabel: string;
    submitLabel: string;
    submittingLabel: string;
    onSave: () => void;
    onSubmit: () => void;
}

/**
 * Returns the appropriate label for the save button based on saving status.
 *
 * @param status - Current saving status
 * @param labels - Label strings keyed by status
 * @returns Resolved label string
 */
// eslint-disable-next-line react-refresh/only-export-components
export function getSaveButtonLabel(
    status: SavingStatus,
    labels: { idle: string; saving: string; saved: string; error: string },
): string {
    return labels[status] ?? labels.idle;
}

/**
 * Reusable save + submit action buttons for the assessment work page.
 *
 * Renders a Save button (with saving state) and a Submit button side by side.
 * Intended to be used both in section header actions and at the bottom of the page.
 *
 * @param savingStatus - Current manual save status
 * @param isSubmitting - Whether a submission is in progress
 * @param processing - Whether Inertia is processing a form
 * @param saveButtonLabel - Label to display on the save button
 * @param submitLabel - Default label for the submit button
 * @param submittingLabel - Label shown while submitting
 * @param onSave - Handler for the save action
 * @param onSubmit - Handler for the submit action
 */
export function WorkSubmitActions({
    savingStatus,
    isSubmitting,
    processing,
    saveButtonLabel,
    submitLabel,
    submittingLabel,
    onSave,
    onSubmit,
}: Props) {
    return (
        <div className="flex items-center space-x-3">
            <Button
                size="sm"
                color="secondary"
                variant="outline"
                onClick={onSave}
                disabled={savingStatus === 'saving'}
                loading={savingStatus === 'saving'}
            >
                {saveButtonLabel}
            </Button>
            <Button
                size="sm"
                color="primary"
                onClick={onSubmit}
                disabled={isSubmitting || processing}
                loading={isSubmitting || processing}
            >
                {isSubmitting || processing ? submittingLabel : submitLabel}
            </Button>
        </div>
    );
}
