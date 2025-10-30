import React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Button } from '@/Components';
import { Exam } from '@/types';
import Section from '@/Components/Section';
import { route } from 'ziggy-js';
import TeacherExamList from '@/Components/exam/TeacherExamList';

interface Props {
    exams: PaginationType<Exam>;
}

const TeacherExamIndex: React.FC<Props> = ({ exams }) => {
    return (
        <AuthenticatedLayout title="Mes examens">
            <Head title="Gestion des examens" />

            <Section title="Gestion des examens" subtitle="Créez, gérez et assignez vos examens aux étudiants."
                actions={<Button
                    size='sm'
                    variant='outline'
                    color='secondary'
                    onClick={() => router.visit(route('teacher.exams.create'))} >
                    Nouvel examen
                </Button>}
            >
                <TeacherExamList
                    data={exams}
                />
            </Section>
        </AuthenticatedLayout>
    );
};

export default TeacherExamIndex;