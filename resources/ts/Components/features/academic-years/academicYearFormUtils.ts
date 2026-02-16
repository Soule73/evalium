import { type SemesterFormData } from '@/types';

export function toDateInputValue(value: string): string {
    if (!value) return '';
    return value.includes('T') ? value.split('T')[0] : value;
}

export function buildDefaultSemesters(startDate: string, endDate: string): SemesterFormData[] {
    if (!startDate || !endDate) {
        return [
            { name: 'Semester 1', start_date: '', end_date: '' },
            { name: 'Semester 2', start_date: '', end_date: '' },
        ];
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    const mid = new Date(start.getTime() + (end.getTime() - start.getTime()) / 2);

    const fmt = (d: Date) => d.toISOString().split('T')[0];
    const nextDay = new Date(mid);
    nextDay.setDate(nextDay.getDate() + 1);

    return [
        { name: 'Semester 1', start_date: fmt(start), end_date: fmt(mid) },
        { name: 'Semester 2', start_date: fmt(nextDay), end_date: fmt(end) },
    ];
}
