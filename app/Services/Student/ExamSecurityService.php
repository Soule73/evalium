<?php

namespace App\Services\Student;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion de la sécurité des examens
 * 
 * Responsabilités :
 * - Détection et logging des violations de sécurité
 * - Gestion des actions suite à une violation
 * - Validation de l'environnement d'examen
 * - Notifications des violations
 */
class ExamSecurityService
{
    /**
     * Types de violations de sécurité supportés
     */
    public const VIOLATION_TAB_SWITCH = 'tab_switch';
    public const VIOLATION_COPY_PASTE = 'copy_paste';
    public const VIOLATION_FULLSCREEN_EXIT = 'fullscreen_exit';
    public const VIOLATION_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const VIOLATION_BROWSER_CHANGE = 'browser_change';
    public const VIOLATION_NETWORK_DISCONNECT = 'network_disconnect';

    /**
     * Violations qui entraînent une terminaison automatique de l'examen
     */
    private const TERMINAL_VIOLATIONS = [
        self::VIOLATION_TAB_SWITCH,
        self::VIOLATION_FULLSCREEN_EXIT,
        self::VIOLATION_BROWSER_CHANGE,
    ];

    /**
     * Enregistre une violation de sécurité
     *
     * @param ExamAssignment $assignment
     * @param string $violationType
     * @param string $details
     * @return void
     */
    public function logViolation(
        ExamAssignment $assignment,
        string $violationType,
        string $details = ''
    ): void {
        Log::warning('Security violation detected', [
            'assignment_id' => $assignment->id,
            'exam_id' => $assignment->exam_id,
            'student_id' => $assignment->student_id,
            'violation_type' => $violationType,
            'details' => $details,
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);

        // Mettre à jour l'assignment avec la violation
        $assignment->update([
            'security_violation' => $violationType,
        ]);
    }

    /**
     * Détermine si une violation doit entraîner la terminaison de l'examen
     *
     * @param string $violationType
     * @return bool
     */
    public function shouldTerminateExam(string $violationType): bool
    {
        return in_array($violationType, self::TERMINAL_VIOLATIONS, true);
    }

    /**
     * Force la soumission de l'examen suite à une violation grave
     *
     * @param ExamAssignment $assignment
     * @param string $violationType
     * @param string $details
     * @return void
     */
    public function forceSubmitDueToViolation(
        ExamAssignment $assignment,
        string $violationType,
        string $details = ''
    ): void {
        $submissionTime = Carbon::now();

        $assignment->update([
            'status' => 'submitted',
            'submitted_at' => $submissionTime,
            'security_violation' => $violationType,
            'forced_submission' => true,
        ]);

        $this->logViolation($assignment, $violationType, $details);
    }

    /**
     * Traite une violation de sécurité complète (log + soumission forcée si nécessaire)
     *
     * @param ExamAssignment $assignment
     * @param string $violationType
     * @param string $details
     * @return bool True si l'examen a été forcé à se terminer
     */
    public function handleViolation(
        ExamAssignment $assignment,
        string $violationType,
        string $details = ''
    ): bool {
        if ($this->shouldTerminateExam($violationType)) {
            $this->forceSubmitDueToViolation($assignment, $violationType, $details);
            return true;
        }

        // Pour les violations non-terminales, on log seulement
        $this->logViolation($assignment, $violationType, $details);
        return false;
    }

    /**
     * Obtient l'historique des violations pour un assignment
     *
     * @param ExamAssignment $assignment
     * @return array
     */
    public function getViolationHistory(ExamAssignment $assignment): array
    {
        // Pour l'instant, on retourne les infos de l'assignment
        // Dans une future version, on pourrait avoir une table séparée pour l'historique
        return [
            'has_violation' => !empty($assignment->security_violation),
            'violation_type' => $assignment->security_violation,
            'forced_submission' => $assignment->forced_submission,
            'submitted_at' => $assignment->submitted_at?->toDateTimeString(),
        ];
    }



    /**
     * Vérifie si un assignment a des violations de sécurité
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function hasViolations(ExamAssignment $assignment): bool
    {
        return !empty($assignment->security_violation);
    }

    /**
     * Obtient le type de violation d'un assignment
     *
     * @param ExamAssignment $assignment
     * @return string|null
     */
    public function getViolationType(ExamAssignment $assignment): ?string
    {
        return $assignment->security_violation;
    }

    /**
     * Vérifie si l'examen a été soumis de force suite à une violation
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function wasForcedSubmission(ExamAssignment $assignment): bool
    {
        return (bool) $assignment->forced_submission;
    }

    /**
     * Obtient tous les types de violations supportés
     *
     * @return array<string>
     */
    public function getSupportedViolationTypes(): array
    {
        return [
            self::VIOLATION_TAB_SWITCH,
            self::VIOLATION_COPY_PASTE,
            self::VIOLATION_FULLSCREEN_EXIT,
            self::VIOLATION_SUSPICIOUS_ACTIVITY,
            self::VIOLATION_BROWSER_CHANGE,
            self::VIOLATION_NETWORK_DISCONNECT,
        ];
    }

    /**
     * Obtient les violations qui entraînent une terminaison automatique
     *
     * @return array<string>
     */
    public function getTerminalViolations(): array
    {
        return self::TERMINAL_VIOLATIONS;
    }

    /**
     * Vérifie si un type de violation est valide
     *
     * @param string $violationType
     * @return bool
     */
    public function isValidViolationType(string $violationType): bool
    {
        return in_array($violationType, $this->getSupportedViolationTypes(), true);
    }
}
