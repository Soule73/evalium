import { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Group } from '@/types';
import { formatDateForInput } from '@/utils';

interface GroupFormData {
    level_id: string;
    start_date: string;
    end_date: string;
    max_students: string;
    academic_year: string;
    is_active: boolean;
}

interface UseGroupFormOptions {
    group?: Group;
}

export function useGroupForm({ group }: UseGroupFormOptions = {}) {
    const defaultAcademicYear = new Date().getFullYear() + '-' + (new Date().getFullYear() + 1);

    const { data, setData, post, put, processing, errors } = useForm<GroupFormData>({
        level_id: group ? (group.level_id ? group.level_id.toString() : '') : '',
        start_date: group ? formatDateForInput(group.start_date) : '',
        end_date: group ? formatDateForInput(group.end_date) : '',
        max_students: group ? group.max_students.toString() : '30',
        academic_year: group ? (group.academic_year || '') : defaultAcademicYear,
        is_active: group ? group.is_active : true,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const routeName = group ? 'groups.update' : 'groups.store';
        const routeParams = group ? { group: group.id } : undefined;
        const method = group ? put : post;

        method(route(routeName, routeParams), {
        });
    };

    const handleCancel = () => {
        const cancelRoute = group
            ? route('groups.show', { group: group.id })
            : route('groups.index');
        router.visit(cancelRoute);
    };

    return {
        formData: data,
        setData,
        errors,
        isSubmitting: processing,
        handleSubmit,
        handleCancel,
    };
}
