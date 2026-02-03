import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Section, Select } from '@/Components';
import { Assessment, AssessmentType } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
import AssessmentCard from '@/Components/teacher/AssessmentCard';

interface Props {
  assessments: PaginationType<Assessment>;
  filters?: {
    search?: string;
    type?: AssessmentType;
    class_subject_id?: number;
    is_published?: boolean;
  };
}

export default function Index({ assessments, filters = {} }: Props) {
  const [searchValue, setSearchValue] = useState(filters.search || '');
  const [selectedType, setSelectedType] = useState<string>(filters.type || 'all');
  const [publishedFilter, setPublishedFilter] = useState<string>(
    filters.is_published !== undefined
      ? (filters.is_published ? 'published' : 'draft')
      : 'all'
  );

  const handleSearch = (value: string) => {
    setSearchValue(value);
    applyFilters({ search: value });
  };

  const handleTypeChange = (value: string | number) => {
    const stringValue = String(value);
    setSelectedType(stringValue);
    applyFilters({ type: stringValue === 'all' ? undefined : stringValue as AssessmentType });
  };

  const handlePublishedChange = (value: string | number) => {
    const stringValue = String(value);
    setPublishedFilter(stringValue);
    applyFilters({
      is_published: stringValue === 'all' ? undefined : stringValue === 'published'
    });
  };

  const applyFilters = (newFilters: Partial<Props['filters']>) => {
    router.get(
      route('teacher.assessments.index'),
      { ...filters, ...newFilters },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleCreate = () => {
    router.visit(route('teacher.assessments.create'));
  };
  const handleView = (assessment: Assessment) => {
    router.visit(route('teacher.assessments.show', assessment.id));
  };

  const typeOptions = [
    { value: 'all', label: trans('teacher_pages.assessments.filters.all_types') },
    { value: 'devoir', label: trans('teacher_pages.assessments.types.devoir') },
    { value: 'examen', label: trans('teacher_pages.assessments.types.examen') },
    { value: 'tp', label: trans('teacher_pages.assessments.types.tp') },
    { value: 'controle', label: trans('teacher_pages.assessments.types.controle') },
    { value: 'projet', label: trans('teacher_pages.assessments.types.projet') },
  ];

  const publishedOptions = [
    { value: 'all', label: trans('teacher_pages.assessments.filters.all_status') },
    { value: 'published', label: trans('teacher_pages.assessments.filters.published') },
    { value: 'draft', label: trans('teacher_pages.assessments.filters.draft') },
  ];

  return (
    <AuthenticatedLayout
      title={trans('teacher_pages.assessments.index.title')}
      breadcrumb={breadcrumbs.teacherAssessments()}
    >
      <Section
        title={trans('teacher_pages.assessments.index.heading')}
        subtitle={trans('teacher_pages.assessments.index.description')}
        actions={
          <Button
            onClick={handleCreate}
            color="primary"
          >
            {trans('teacher_pages.assessments.index.create_button')}
          </Button>
        }
      >
        <div className="mb-6 flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder={trans('teacher_pages.assessments.filters.search_placeholder')}
              value={searchValue}
              onChange={(e) => handleSearch(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div className="w-full sm:w-48">
            <Select
              value={selectedType}
              onChange={handleTypeChange}
              options={typeOptions}
            />
          </div>
          <div className="w-full sm:w-48">
            <Select
              value={publishedFilter}
              onChange={handlePublishedChange}
              options={publishedOptions}
            />
          </div>
        </div>

        {assessments.data.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-500 text-lg mb-4">
              {trans('teacher_pages.assessments.index.no_assessments')}
            </p>
            <Button onClick={handleCreate} color="primary">
              {trans('teacher_pages.assessments.index.create_first')}
            </Button>
          </div>
        ) : (
          <div className="space-y-4">
            {assessments.data.map((assessment) => (
              <AssessmentCard
                key={assessment.id}
                assessment={assessment}
                onClick={() => handleView(assessment)}
              />
            ))}
          </div>
        )}
      </Section>
    </AuthenticatedLayout>
  );
}