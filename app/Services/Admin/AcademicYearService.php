<?php

namespace App\Services\Admin;

use App\Models\AcademicYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Academic Year Service - Manage academic year lifecycle
 *
 * Single Responsibility: Handle academic year CRUD, activation, and semester creation
 */
class AcademicYearService
{
    /**
     * Create a new academic year with its semesters.
     */
    public function createNewYear(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            if ($data['is_current'] ?? false) {
                $this->deactivateCurrentYear();
            }

            $academicYear = AcademicYear::create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $data['is_current'] ?? false,
            ]);

            $this->syncSemesters($academicYear, $data['semesters'] ?? []);

            return $academicYear->load('semesters');
        });
    }

    /**
     * Set an academic year as current (deactivate others)
     */
    public function setCurrentYear(AcademicYear $academicYear): AcademicYear
    {
        return DB::transaction(function () use ($academicYear) {
            $this->deactivateCurrentYear();

            $academicYear->update(['is_current' => true]);

            return $academicYear->fresh();
        });
    }

    /**
     * Get the current academic year
     */
    public function getCurrentYear(): ?AcademicYear
    {
        return AcademicYear::current()->first();
    }

    /**
     * Archive an academic year (set is_current to false)
     */
    public function archiveYear(AcademicYear $academicYear): AcademicYear
    {
        $academicYear->update(['is_current' => false]);

        return $academicYear->fresh();
    }

    /**
     * Update an existing academic year and sync its semesters.
     */
    public function updateYear(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data) {
            if (isset($data['is_current']) && $data['is_current'] && ! $academicYear->is_current) {
                $this->deactivateCurrentYear();
            }

            $academicYear->update([
                'name' => $data['name'] ?? $academicYear->name,
                'start_date' => $data['start_date'] ?? $academicYear->start_date,
                'end_date' => $data['end_date'] ?? $academicYear->end_date,
                'is_current' => $data['is_current'] ?? $academicYear->is_current,
            ]);

            if (isset($data['semesters'])) {
                $this->syncSemesters($academicYear, $data['semesters']);
            }

            return $academicYear->fresh()->load('semesters');
        });
    }

    /**
     * Delete an academic year (only if not current and has no classes)
     */
    public function deleteYear(AcademicYear $academicYear): bool
    {
        if ($academicYear->is_current) {
            throw new InvalidArgumentException('Cannot delete the current academic year');
        }

        if ($academicYear->classes()->count() > 0) {
            throw new InvalidArgumentException('Cannot delete academic year with existing classes');
        }

        return $academicYear->delete();
    }

    /**
     * Sync semesters: create new, update existing, remove deleted.
     *
     * @param  array<int, array{id?: int, name: string, start_date: string, end_date: string}>  $semestersData
     */
    private function syncSemesters(AcademicYear $academicYear, array $semestersData): void
    {
        $incomingIds = collect($semestersData)
            ->pluck('id')
            ->filter()
            ->toArray();

        $academicYear->semesters()
            ->whereNotIn('id', $incomingIds)
            ->delete();

        foreach ($semestersData as $index => $semesterData) {
            $attributes = [
                'name' => $semesterData['name'],
                'start_date' => $semesterData['start_date'],
                'end_date' => $semesterData['end_date'],
                'order_number' => $index + 1,
            ];

            if (! empty($semesterData['id'])) {
                $academicYear->semesters()
                    ->where('id', $semesterData['id'])
                    ->update($attributes);
            } else {
                $academicYear->semesters()->create($attributes);
            }
        }
    }

    /**
     * Deactivate all current academic years
     */
    private function deactivateCurrentYear(): void
    {
        AcademicYear::where('is_current', true)->update(['is_current' => false]);
    }

    /**
     * Get academic years for archives page with pagination and filters.
     */
    public function getAcademicYearsForArchives(array $filters, int $perPage): LengthAwarePaginator
    {
        return AcademicYear::query()
            ->with('semesters')
            ->withCount(['semesters', 'classes'])
            ->when(
                $filters['search'] ?? null,
                fn($query, $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                isset($filters['is_current']),
                fn($query) => $query->where('is_current', (bool) $filters['is_current'])
            )
            ->orderBy('start_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }
}
