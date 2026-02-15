<?php

namespace App\Traits;

use Illuminate\Validation\Validator;

/**
 * Shared validation logic for academic year form requests.
 *
 * Provides semester date validation rules, custom messages,
 * and the withValidator hook for overlap/bounds checking.
 */
trait ValidatesAcademicYearSemesters
{
  /**
   * Get shared semester validation rules.
   *
   * @return array<string, array<int, mixed>>
   */
  protected function semesterRules(): array
  {
    return [
      'start_date' => ['required', 'date'],
      'end_date' => ['required', 'date', 'after:start_date'],
      'is_current' => ['sometimes', 'boolean'],
      'semesters' => ['required', 'array', 'min:1'],
      'semesters.*.name' => ['required', 'string', 'max:255'],
      'semesters.*.start_date' => ['required', 'date'],
      'semesters.*.end_date' => ['required', 'date'],
    ];
  }

  /**
   * Get shared validation messages for academic year fields.
   *
   * @return array<string, string>
   */
  protected function semesterMessages(): array
  {
    return [
      'name.required' => __('validation.required', ['attribute' => __('messages.academic_year_name')]),
      'name.unique' => __('validation.unique', ['attribute' => __('messages.academic_year_name')]),
      'start_date.required' => __('validation.required', ['attribute' => __('messages.start_date')]),
      'end_date.required' => __('validation.required', ['attribute' => __('messages.end_date')]),
      'end_date.after' => __('validation.after', ['attribute' => __('messages.end_date'), 'date' => __('messages.start_date')]),
      'semesters.required' => __('messages.semesters_required'),
      'semesters.min' => __('messages.semesters_min'),
      'semesters.*.name.required' => __('messages.semester_name_required'),
      'semesters.*.start_date.required' => __('messages.semester_start_date_required'),
      'semesters.*.end_date.required' => __('messages.semester_end_date_required'),
    ];
  }

  /**
   * Configure the validator instance with semester overlap checks.
   */
  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator) {
      $this->validateSemesterDates($validator);
    });
  }

  /**
   * Validate semester dates: within year bounds, end > start, no overlaps.
   */
  private function validateSemesterDates(Validator $validator): void
  {
    $semesters = $this->input('semesters', []);
    $yearStart = $this->input('start_date');
    $yearEnd = $this->input('end_date');

    if (! $yearStart || ! $yearEnd || empty($semesters)) {
      return;
    }

    $yearStartDate = strtotime($yearStart);
    $yearEndDate = strtotime($yearEnd);

    $validSemesters = [];

    foreach ($semesters as $index => $semester) {
      $start = $semester['start_date'] ?? null;
      $end = $semester['end_date'] ?? null;

      if (! $start || ! $end) {
        continue;
      }

      $startTs = strtotime($start);
      $endTs = strtotime($end);

      if ($endTs <= $startTs) {
        $validator->errors()->add(
          "semesters.{$index}.end_date",
          __('messages.semester_end_before_start')
        );

        continue;
      }

      if ($startTs < $yearStartDate || $startTs > $yearEndDate) {
        $validator->errors()->add(
          "semesters.{$index}.start_date",
          __('messages.semester_outside_year')
        );
      }

      if ($endTs < $yearStartDate || $endTs > $yearEndDate) {
        $validator->errors()->add(
          "semesters.{$index}.end_date",
          __('messages.semester_outside_year')
        );
      }

      foreach ($validSemesters as [$prevIndex, $prevStart, $prevEnd]) {
        if ($startTs < $prevEnd && $endTs > $prevStart) {
          $validator->errors()->add(
            "semesters.{$index}.start_date",
            __('messages.semester_overlap', ['other' => $prevIndex + 1])
          );
          break;
        }
      }

      $validSemesters[] = [$index, $startTs, $endTs];
    }
  }
}
