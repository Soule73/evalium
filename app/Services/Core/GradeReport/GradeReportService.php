<?php

namespace App\Services\Core\GradeReport;

use App\Enums\GradeReportStatus;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\GradeReport;
use App\Models\Semester;
use App\Models\User;
use App\Services\Core\GradeCalculationService;
use App\Settings\BulletinSettings;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Orchestrates grade report lifecycle: draft generation, validation, PDF, publishing.
 */
class GradeReportService
{
  public function __construct(
    private readonly GradeCalculationService $gradeCalculationService,
    private readonly RemarkGeneratorService $remarkGeneratorService,
    private readonly GeneralSettings $generalSettings,
    private readonly BulletinSettings $bulletinSettings
  ) {}

  /**
   * Generate draft grade reports for all active enrollments in a class.
   *
   * @return Collection<int, GradeReport>
   */
  public function generateDrafts(ClassModel $class, ?Semester $semester = null): Collection
  {
    $enrollments = Enrollment::where('class_id', $class->id)
      ->active()
      ->with(['student', 'class.level', 'class.academicYear'])
      ->get();

    $ranking = $this->gradeCalculationService->calculateClassRanking($class, $semester);
    $rankingMap = collect($ranking)->keyBy('enrollment_id');

    $classSize = $enrollments->count();

    $subjectStats = [];
    if ($this->bulletinSettings->show_class_average || $this->bulletinSettings->show_min_max) {
      $subjectStats = $this->computeSubjectStats($class, $semester);
    }

    $reports = collect();

    foreach ($enrollments as $enrollment) {
      $report = $this->generateDraftForEnrollment($enrollment, $semester, $rankingMap, $classSize, $subjectStats);
      $reports->push($report);
    }

    return $reports;
  }

  /**
   * Generate or update a single draft report for one enrollment.
   *
   * @param  array<int, array{class_subject_id: int, class_average: float|null, min: float|null, max: float|null}>  $subjectStats  Pre-computed class-level stats
   */
  private function generateDraftForEnrollment(
    Enrollment $enrollment,
    ?Semester $semester,
    Collection $rankingMap,
    int $classSize,
    array $subjectStats
  ): GradeReport {
    $enrollment->loadMissing(['student', 'class.level', 'class.academicYear']);

    if ($semester) {
      $breakdown = $this->gradeCalculationService->getSemesterGradeBreakdown($enrollment, $semester);
      $average = $breakdown['semester_average'];
    } else {
      $breakdown = $this->gradeCalculationService->getGradeBreakdown($enrollment->student, $enrollment->class);
      $average = $breakdown['annual_average'];
    }

    $rankEntry = $rankingMap->get($enrollment->id);
    $rank = $rankEntry['rank'] ?? null;

    $subjectsData = $this->buildSubjectsData($breakdown['subjects'], $subjectStats);

    $subjectRemarks = $this->remarkGeneratorService->forSubjects(
      array_map(fn($s) => [
        'class_subject_id' => $s['class_subject_id'],
        'subject_name' => $s['subject_name'],
        'grade' => $s['average'] ?? null,
      ], $breakdown['subjects'])
    );

    $generalRemark = $this->remarkGeneratorService->forOverallAverage($average);

    $data = [
      'header' => [
        'school_name' => $this->generalSettings->school_name,
        'logo_path' => $this->generalSettings->logo_path,
        'academic_year' => $enrollment->class->academicYear->name ?? '',
        'period' => $semester?->name ?? __('messages.annual'),
        'student_name' => $enrollment->student->name,
        'class_name' => $enrollment->class->name,
        'level_name' => $enrollment->class->level?->name ?? '',
      ],
      'subjects' => $subjectsData,
      'footer' => [
        'average' => $average,
        'rank' => $this->bulletinSettings->show_ranking ? $rank : null,
        'class_size' => $classSize,
        'total_coefficient' => $breakdown['total_coefficient'],
      ],
    ];

    return GradeReport::updateOrCreate(
      [
        'enrollment_id' => $enrollment->id,
        'semester_id' => $semester?->id,
        'academic_year_id' => $enrollment->class->academic_year_id,
      ],
      [
        'data' => $data,
        'remarks' => ['subjects' => $subjectRemarks],
        'general_remark' => $generalRemark,
        'rank' => $rank,
        'average' => $average,
        'status' => GradeReportStatus::Draft,
        'validated_by' => null,
        'validated_at' => null,
        'file_path' => null,
      ]
    );
  }

  /**
   * Update subject remarks on a draft report (teacher action).
   *
   * @param  array<int, array{class_subject_id: int, remark: string}>  $subjectRemarks
   */
  public function updateRemarks(GradeReport $report, array $subjectRemarks): GradeReport
  {
    $currentRemarks = $report->remarks ?? ['subjects' => []];

    foreach ($subjectRemarks as $update) {
      foreach ($currentRemarks['subjects'] as &$existing) {
        if ($existing['class_subject_id'] === $update['class_subject_id']) {
          $existing['remark'] = $update['remark'];
          $existing['auto_generated'] = false;
        }
      }
      unset($existing);
    }

    $report->update(['remarks' => $currentRemarks]);

    return $report->refresh();
  }

  /**
   * Update the general remark on a report (admin action).
   */
  public function updateGeneralRemark(GradeReport $report, string $remark): GradeReport
  {
    $report->update(['general_remark' => $remark]);

    return $report->refresh();
  }

  /**
   * Validate a report: freeze data snapshot and generate PDF.
   */
  public function validate(GradeReport $report, User $validator): GradeReport
  {
    $report->update([
      'status' => GradeReportStatus::Validated,
      'validated_by' => $validator->id,
      'validated_at' => now(),
    ]);

    $this->generatePdf($report);

    return $report->refresh();
  }

  /**
   * Validate all draft reports for a class in batch.
   *
   * @return int Number of validated reports
   */
  public function validateBatch(ClassModel $class, ?Semester $semester, User $validator): int
  {
    $query = GradeReport::where('academic_year_id', $class->academic_year_id)
      ->where('status', GradeReportStatus::Draft)
      ->whereHas('enrollment', fn($q) => $q->where('class_id', $class->id));

    if ($semester) {
      $query->where('semester_id', $semester->id);
    } else {
      $query->whereNull('semester_id');
    }

    $reports = $query->get();

    foreach ($reports as $report) {
      $this->validate($report, $validator);
    }

    return $reports->count();
  }

  /**
   * Generate PDF file for a validated report.
   */
  public function generatePdf(GradeReport $report): string
  {
    $content = $this->renderPdfContent($report);

    $directory = 'grade-reports/' . $report->academic_year_id;
    Storage::disk('local')->makeDirectory($directory);

    $filename = sprintf(
      '%s/report_%d_%s.pdf',
      $directory,
      $report->id,
      str_replace(' ', '_', $report->enrollment?->student?->name ?? 'student')
    );

    Storage::disk('local')->put($filename, $content);

    $report->update(['file_path' => $filename]);

    return $filename;
  }

  /**
   * Render the PDF content for a report without persisting it.
   */
  public function renderPdfContent(GradeReport $report): string
  {
    $report->loadMissing(['enrollment.student', 'enrollment.class.level', 'semester']);

    $logoBase64 = null;
    $logoPath = $report->data['header']['logo_path'] ?? $this->generalSettings->logo_path;
    if ($logoPath && Storage::disk('public')->exists($logoPath)) {
      $logoContent = Storage::disk('public')->get($logoPath);
      $mimeType = Storage::disk('public')->mimeType($logoPath);
      $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
    }

    $pdf = Pdf::loadView('pdf.grade-report', [
      'report' => $report,
      'data' => $report->data,
      'remarks' => $report->remarks,
      'generalRemark' => $report->general_remark,
      'showRanking' => $this->bulletinSettings->show_ranking,
      'showClassAverage' => $this->bulletinSettings->show_class_average,
      'showMinMax' => $this->bulletinSettings->show_min_max,
      'logoBase64' => $logoBase64,
    ])->setPaper('a4', 'portrait');

    return $pdf->output();
  }

  /**
   * Generate a ZIP archive of all validated reports for a class.
   */
  public function generateBatchPdf(ClassModel $class, ?Semester $semester): string
  {
    $query = GradeReport::where('academic_year_id', $class->academic_year_id)
      ->whereIn('status', [GradeReportStatus::Validated, GradeReportStatus::Published])
      ->whereHas('enrollment', fn($q) => $q->where('class_id', $class->id));

    if ($semester) {
      $query->where('semester_id', $semester->id);
    } else {
      $query->whereNull('semester_id');
    }

    $reports = $query->with('enrollment.student')->get();

    foreach ($reports as $report) {
      if (! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
        $this->generatePdf($report);
      }
    }

    $zipFilename = sprintf(
      'grade-reports/%d/batch_%s_%s.zip',
      $class->academic_year_id,
      str_replace(' ', '_', $class->name),
      $semester?->name ? str_replace(' ', '_', $semester->name) : 'annual'
    );

    $zipPath = Storage::disk('local')->path($zipFilename);
    Storage::disk('local')->makeDirectory(dirname($zipFilename));

    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new \RuntimeException("Failed to create ZIP archive at: {$zipPath}");
    }

    foreach ($reports as $report) {
      if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
        $studentName = str_replace(' ', '_', $report->enrollment?->student?->name ?? 'student');
        $zip->addFile(
          Storage::disk('local')->path($report->file_path),
          "bulletin_{$report->id}_{$studentName}.pdf"
        );
      }
    }

    $zip->close();

    return $zipFilename;
  }

  /**
   * Publish a validated report (makes it visible to the student).
   */
  public function publish(GradeReport $report): GradeReport
  {
    $report->update(['status' => GradeReportStatus::Published]);

    return $report->refresh();
  }

  /**
   * Compute class-level statistics per subject (average, min, max) using batch queries.
   *
   * Loads all graded assignments in a single query instead of per-student per-subject,
   * reducing query count from O(students x subjects) to O(1).
   *
   * @return array<int, array{class_subject_id: int, class_average: float|null, min: float|null, max: float|null}>
   */
  private function computeSubjectStats(ClassModel $class, ?Semester $semester): array
  {
    $enrollmentIds = Enrollment::where('class_id', $class->id)
      ->active()
      ->pluck('id');

    $classSubjects = $class->classSubjects()
      ->active()
      ->when($semester, fn($q) => $q->where('semester_id', $semester->id))
      ->get();

    $classSubjectIds = $classSubjects->pluck('id');

    $allAssignments = AssessmentAssignment::whereIn('enrollment_id', $enrollmentIds)
      ->whereNotNull('graded_at')
      ->whereHas('assessment', fn($q) => $q->whereIn('class_subject_id', $classSubjectIds))
      ->withSum('answers', 'score')
      ->with(['assessment:id,class_subject_id,coefficient', 'assessment.questions:id,assessment_id,points'])
      ->get();

    $grouped = $allAssignments
      ->groupBy(fn($a) => $a->assessment->class_subject_id)
      ->map(fn($items) => $items->groupBy('enrollment_id'));

    $stats = [];

    foreach ($classSubjects as $cs) {
      $grades = [];
      $enrollmentsForSubject = $grouped->get($cs->id, collect());

      foreach ($enrollmentsForSubject as $enrollmentAssignments) {
        $triplets = $enrollmentAssignments->map(fn($a) => [
          'coefficient' => $a->assessment->coefficient,
          'score' => $a->score,
          'max_points' => $a->assessment->questions->sum('points'),
        ])->toArray();

        $grade = $this->gradeCalculationService->computeWeightedGrade($triplets);
        if ($grade !== null) {
          $grades[] = $grade;
        }
      }

      $stats[$cs->id] = [
        'class_subject_id' => $cs->id,
        'class_average' => count($grades) > 0 ? round(array_sum($grades) / count($grades), 2) : null,
        'min' => count($grades) > 0 ? min($grades) : null,
        'max' => count($grades) > 0 ? max($grades) : null,
      ];
    }

    return $stats;
  }

  /**
   * Build enriched subjects data array with optional stats.
   *
   * @param  array  $subjects  Subject breakdown from GradeCalculationService
   * @param  array  $subjectStats  Class-level stats keyed by class_subject_id
   */
  private function buildSubjectsData(array $subjects, array $subjectStats): array
  {
    return array_map(function ($subject) use ($subjectStats) {
      $entry = [
        'class_subject_id' => $subject['class_subject_id'],
        'subject_name' => $subject['subject_name'],
        'coefficient' => $subject['coefficient'],
        'grade' => $subject['average'] ?? null,
        'assessments_count' => $subject['assessments_count'] ?? $subject['completed_count'] ?? 0,
      ];

      $csId = $subject['class_subject_id'];
      if ($this->bulletinSettings->show_class_average && isset($subjectStats[$csId])) {
        $entry['class_average'] = $subjectStats[$csId]['class_average'];
      }

      if ($this->bulletinSettings->show_min_max && isset($subjectStats[$csId])) {
        $entry['min'] = $subjectStats[$csId]['min'];
        $entry['max'] = $subjectStats[$csId]['max'];
      }

      return $entry;
    }, $subjects);
  }
}
