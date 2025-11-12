import React from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Button, ExamList, Section } from '@/Components';
import { Exam, PageProps } from '@/types';
import { route } from 'ziggy-js';
import { hasPermission } from '@/utils';
import { trans } from '@/utils';
import { breadcrumbs } from '@/utils';

interface Props extends PageProps {
    exams: PaginationType<Exam>;
}

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