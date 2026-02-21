import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Timeline } from '@evalium/ui';
import type { TimelineStep } from '@evalium/ui';
import {
    AcademicYearWizardProvider,
    useAcademicYearWizard,
} from '@/contexts/AcademicYearWizardContext';
import {
    AcademicYearFormStep,
    AcademicYearConfirmStep,
    AcademicYearResultStep,
    ClassDuplicationStep,
} from '@/Components/features/academic-years';
import type { AcademicYear, PageProps } from '@/types';

interface Props extends PageProps {
    currentYear: AcademicYear | null;
    futureYearExists: boolean;
}

interface WizardStepsProps {
    currentYear: AcademicYear | null;
    futureYearExists: boolean;
}

function WizardSteps({ currentYear, futureYearExists }: WizardStepsProps) {
    const { t } = useTranslations();
    const { state } = useAcademicYearWizard();

    const steps: TimelineStep[] = useMemo(
        () => [
            { label: t('admin_pages.academic_years.wizard_step_1_label') },
            { label: t('admin_pages.academic_years.wizard_step_2_label') },
            { label: t('admin_pages.academic_years.wizard_step_3_label') },
        ],
        [t],
    );

    const currentStep = state.step === 'result' ? 3 : (state.step as number);
    const isResultStep = state.step === 'result';

    return (
        <div className="space-y-8">
            {!isResultStep && <Timeline steps={steps} currentStep={currentStep} />}

            {state.step === 1 && (
                <AcademicYearFormStep
                    currentYear={currentYear}
                    futureYearExists={futureYearExists}
                />
            )}

            {state.step === 2 && currentYear && (
                <ClassDuplicationStep currentYear={currentYear} />
            )}

            {state.step === 3 && <AcademicYearConfirmStep currentYear={currentYear} />}

            {state.step === 'result' && <AcademicYearResultStep />}
        </div>
    );
}

export default function AcademicYearCreate({ currentYear, futureYearExists }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const initialClassIds = useMemo(
        () => currentYear?.classes?.map((c) => c.id) ?? [],
        [currentYear],
    );

    return (
        <AuthenticatedLayout
            title={t('admin_pages.academic_years.create_page_title')}
            breadcrumb={breadcrumbs.admin.createAcademicYear()}
        >
            <AcademicYearWizardProvider initialClassIds={initialClassIds}>
                <WizardSteps currentYear={currentYear} futureYearExists={futureYearExists} />
            </AcademicYearWizardProvider>
        </AuthenticatedLayout>
    );
}

