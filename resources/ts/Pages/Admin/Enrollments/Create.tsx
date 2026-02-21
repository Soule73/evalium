import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Timeline } from '@evalium/ui';
import type { TimelineStep } from '@evalium/ui';
import { EnrollmentWizardProvider, useEnrollmentWizard } from '@/contexts/EnrollmentWizardContext';
import { ClassSelectionStep } from '@/Components/features/enrollments/ClassSelectionStep';
import { StudentSelectionStep } from '@/Components/features/enrollments/StudentSelectionStep';
import { EnrollmentSummaryStep } from '@/Components/features/enrollments/EnrollmentSummaryStep';
import { EnrollmentResultStep } from '@/Components/features/enrollments/EnrollmentResultStep';

interface Props {
    selectedYearId: number | null;
}

function WizardSteps({ selectedYearId }: Props) {
    const { t } = useTranslations();
    const { state } = useEnrollmentWizard();

    const steps: TimelineStep[] = [
        { label: t('admin_pages.enrollments.step_1_label') },
        { label: t('admin_pages.enrollments.step_2_label') },
        { label: t('admin_pages.enrollments.step_3_label') },
    ];

    const currentStep = state.step === 'result' ? 3 : (state.step as number);
    const isResultStep = state.step === 'result';

    return (
        <div className="space-y-8">
            {!isResultStep && <Timeline steps={steps} currentStep={currentStep} />}

            {state.step === 1 && <ClassSelectionStep selectedYearId={selectedYearId} />}

            {state.step === 2 && state.selectedClass && (
                <StudentSelectionStep selectedClass={state.selectedClass} />
            )}

            {state.step === 3 && <EnrollmentSummaryStep />}

            {state.step === 'result' && <EnrollmentResultStep />}
        </div>
    );
}

export default function EnrollmentCreate({ selectedYearId }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('admin_pages.enrollments.create_title')}
            breadcrumb={breadcrumbs.admin.createEnrollment()}
        >
            <EnrollmentWizardProvider>
                <WizardSteps selectedYearId={selectedYearId} />
            </EnrollmentWizardProvider>
        </AuthenticatedLayout>
    );
}
