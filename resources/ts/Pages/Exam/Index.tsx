import React from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Button } from '@/Components';
import { Exam, PageProps } from '@/types';
import Section from '@/Components/Section';
import { route } from 'ziggy-js';
import ExamList from '@/Components/exam/ExamList';
import { hasPermission } from '@/utils/permissions';
import { trans } from '@/utils/translations';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Props extends PageProps {
    exams: PaginationType<Exam>;
}

/**
 * Page Index des examens - Interface UNIFIÉE basée sur permissions
 * 
 * STRATÉGIE HYBRIDE :
 * - Students : Utilisent /student/exams (Student/ExamIndex.tsx)
 * - Autres rôles : Cette page avec affichage conditionnel selon permissions
 */
const ExamIndex: React.FC<Props> = ({ exams }) => {
    const { auth } = usePage<PageProps>().props;
    const canCreateExams = hasPermission(auth.permissions, 'create exams');

    return (
        <AuthenticatedLayout title={trans('exam_pages.page_titles.index')}
            breadcrumb={breadcrumbs.exams()}
        >

            <Section
                title={trans('exam_pages.index.title')}
                subtitle={trans('exam_pages.index.subtitle')}
                actions={canCreateExams && (
                    <Button
                        size='sm'
                        variant='outline'
                        color='secondary'
                        onClick={() => router.visit(route('exams.create'))}
                    >
                        {trans('exam_pages.index.new_exam')}
                    </Button>
                )}
            >
                <ExamList data={exams} />
            </Section>
        </AuthenticatedLayout>
    );
};

export default ExamIndex;