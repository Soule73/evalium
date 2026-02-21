import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { DataTable } from '@/Components/shared/datatable';
import { Button, Section } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import { useEnrollmentWizard } from '@/contexts/EnrollmentWizardContext';
import CreateUserModal from '@/Components/features/users/CreateUserModal';
import type { User, CreatedUserCredentials } from '@/types';
import type { ClassModel } from '@/types';
import type { PaginationType, DataTableConfig, DataTableState } from '@/types/datatable';
import { ArrowLeftIcon, ArrowRightIcon, UserPlusIcon } from '@heroicons/react/24/outline';

interface StudentSelectionStepProps {
  selectedClass: ClassModel;
}

/**
 * Step 2 of the enrollment wizard: select one or more students using a multi-select DataTable.
 * Allows creating new student accounts inline via a modal.
 */
export function StudentSelectionStep({
  selectedClass,
}: StudentSelectionStepProps) {
  const { t } = useTranslations();
  const { state, actions } = useEnrollmentWizard();

  const [data, setData] = useState<PaginationType<User> | undefined>(undefined);
  const [isLoading, setIsLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedIds, setSelectedIds] = useState<(number | string)[]>(
    state.selectedStudents.map((s) => s.id),
  );
  const localStudentMapRef = useRef<Map<number, User>>(
    new Map(state.selectedStudents.map((s) => [s.id, s])),
  );

  const lastStateRef = useRef<DataTableState>({ search: '', filters: {}, page: 1, perPage: 15 });

  const fetchStudents = useCallback(
    async (tableState: DataTableState) => {
      lastStateRef.current = tableState;
      setIsLoading(true);
      try {
        const response = await axios.get<PaginationType<User>>(
          route('admin.enrollments.search-students'),
          {
            params: {
              q: tableState.search,
              page: tableState.page,
              per_page: tableState.perPage,
              class_id: selectedClass.id,
            },
          },
        );
        setData(response.data);
      } finally {
        setIsLoading(false);
      }
    },
    [selectedClass.id],
  );

  useEffect(() => {
    fetchStudents({ search: '', filters: {}, page: 1, perPage: 15 });
  }, [fetchStudents]);

  const handleStateChange = useCallback(
    (tableState: DataTableState) => {
      fetchStudents(tableState);
    },
    [fetchStudents],
  );

  const handleSelectionChange = useCallback(
    (ids: (number | string)[]) => {
      setSelectedIds(ids);
      const resolved: User[] = ids.map((id) => {
        const numId = Number(id);
        const fromPage = data?.data.find((s) => s.id === numId);
        if (fromPage) {
          localStudentMapRef.current.set(numId, fromPage);
          return fromPage;
        }
        return localStudentMapRef.current.get(numId) ?? ({ id: numId } as User);
      });
      actions.setSelectedStudents(resolved);
    },
    [data?.data, actions],
  );

  const handleStudentCreated = useCallback(
    (credentials: CreatedUserCredentials) => {
      const newStudent: User = {
        id: credentials.id,
        name: credentials.name,
        email: credentials.email,
        avatar: null,
        roles: ['student'],
      } as unknown as User;

      localStudentMapRef.current.set(credentials.id, newStudent);

      actions.addNewlyCreatedStudent(credentials, newStudent);

      setSelectedIds((prev) => {
        const already = prev.includes(credentials.id);
        return already ? prev : [...prev, credentials.id];
      });

      fetchStudents(lastStateRef.current);
    },
    [actions, fetchStudents],
  );

  const handleNext = useCallback(() => {
    actions.goToStep(3);
  }, [actions]);

  const handleBack = useCallback(() => {
    actions.goToStep(1);
  }, [actions]);

  const config: DataTableConfig<User> = useMemo(
    () => ({
      searchPlaceholder: t('admin_pages.enrollments.search_students'),
      enableSelection: true,
      columns: [
        {
          key: 'name',
          label: t('admin_pages.users.name'),
          render: (student) => (
            <div>
              <div className="font-medium text-gray-900">{student.name}</div>
              <div className="text-sm text-gray-500">{student.email}</div>
            </div>
          ),
        },
      ],
      selectionActions: (ids) => (
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="ghost"
            color="primary"
            size="sm"
            onClick={handleBack}
          >
            <ArrowLeftIcon className="mr-2 h-4 w-4" />
            {t('admin_pages.enrollments.back')}
          </Button>
          <Button
            type="button"
            color="primary"
            variant="solid"
            size="sm"
            onClick={handleNext}
            disabled={ids.length === 0}
          >
            {t('admin_pages.enrollments.next_step')} ({ids.length})
            <ArrowRightIcon className="ml-2 h-4 w-4" />
          </Button>
        </div>
      ),
      emptyState: {
        title: t('admin_pages.enrollments.no_students_title'),
        subtitle: t('admin_pages.enrollments.no_students_subtitle'),
      },
    }),
    [t, handleBack, handleNext],
  );

  return (
    <>
      <Section
        title={t('admin_pages.enrollments.step_select_students')}
        actions={
          <Button
            type="button"
            color="primary"
            variant="outline"
            size="sm"
            onClick={() => setShowCreateModal(true)}
          >
            <UserPlusIcon className="mr-2 h-4 w-4" />
            {t('admin_pages.enrollments.create_student')}
          </Button>
        }
      >
        <DataTable
          data={data ?? []}
          config={config}
          isLoading={isLoading}
          onStateChange={handleStateChange}
          onSelectionChange={handleSelectionChange}
          selectedIds={selectedIds}
        />
      </Section>

      {!isLoading && selectedIds.length === 0 && (
        <div className="mt-4 flex justify-start">
          <Button
            type="button"
            variant="ghost"
            color="primary"
            size="sm"
            onClick={handleBack}
          >
            <ArrowLeftIcon className="mr-2 h-4 w-4" />
            {t('admin_pages.enrollments.back')}
          </Button>
        </div>
      )}

      <CreateUserModal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        forcedRole="student"
        storeRoute="admin.enrollments.create-student"
        hideSendCredentials
        onStudentCreated={handleStudentCreated}
      />
    </>
  );
}
