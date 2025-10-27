<?php

namespace App\Services\Student;

use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Carbon;

/**
 * Service responsable de la gestion du timing des examens
 * 
 * Ce service centralise toute la logique liée aux contraintes temporelles :
 * - Validation des dates/heures d'accès
 * - Calcul de durée et temps restant
 * - Gestion des prolongations
 * - Détection d'expiration
 * 
 * Extrait depuis ExamSessionService pour respecter le principe SRP.
 */
class ExamTimingService
{
    /**
     * Valide si l'examen est accessible selon ses contraintes temporelles
     *
     * @param Exam $exam L'examen à valider
     * @param Carbon|null $now La date/heure de référence (null = maintenant)
     * @return bool True si l'examen est accessible
     */
    public function validateExamTiming(Exam $exam, ?Carbon $now = null): bool
    {
        $now = $now ?? Carbon::now();

        // Si pas de contraintes temporelles, l'examen est toujours accessible
        if (!$exam->start_time || !$exam->end_time) {
            return true;
        }

        return $now->between($exam->start_time, $exam->end_time);
    }

    /**
     * Vérifie si l'examen est actuellement accessible
     * 
     * Alias de validateExamTiming pour plus de clarté sémantique
     *
     * @param Exam $exam
     * @param Carbon|null $now
     * @return bool
     */
    public function isExamAccessible(Exam $exam, ?Carbon $now = null): bool
    {
        return $this->validateExamTiming($exam, $now);
    }

    /**
     * Vérifie si l'examen est dans sa période de disponibilité
     * 
     * Différent de isExamAccessible : ne prend pas en compte l'assignment
     *
     * @param Exam $exam
     * @return bool
     */
    public function isWithinAvailabilityWindow(Exam $exam): bool
    {
        $now = Carbon::now();

        if (!$exam->start_time) {
            return true; // Pas de date de début = toujours disponible
        }

        if (!$exam->end_time) {
            return $now->greaterThanOrEqualTo($exam->start_time);
        }

        return $now->between($exam->start_time, $exam->end_time);
    }

    /**
     * Calcule le temps restant pour compléter l'examen (en secondes)
     *
     * @param ExamAssignment $assignment
     * @return int|null Nombre de secondes restantes, null si pas de limite
     */
    public function getTimeRemaining(ExamAssignment $assignment): ?int
    {
        if (!$assignment->started_at) {
            return null;
        }

        $exam = $assignment->exam;

        // Si pas de durée définie (0 ou null), pas de limite de temps
        if (!$exam->duration || $exam->duration <= 0) {
            return null;
        }

        $endTime = $this->calculateExamEndTime($assignment);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($endTime)) {
            return 0; // Temps écoulé
        }

        return $now->diffInSeconds($endTime, false);
    }

    /**
     * Calcule l'heure de fin de l'examen pour une assignation donnée
     * 
     * Prend en compte :
     * - La durée de l'examen
     * - L'heure de démarrage
     *
     * @param ExamAssignment $assignment
     * @return Carbon
     */
    public function calculateExamEndTime(ExamAssignment $assignment): Carbon
    {
        $exam = $assignment->exam;
        $startedAt = $assignment->started_at ?? Carbon::now();

        // Durée en minutes (traiter 0 comme 60 par défaut)
        $durationMinutes = ($exam->duration && $exam->duration > 0) ? $exam->duration : 60;

        return $startedAt->copy()->addMinutes($durationMinutes);
    }

    /**
     * Vérifie si l'examen a expiré (temps écoulé)
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function isExamExpired(ExamAssignment $assignment): bool
    {
        $timeRemaining = $this->getTimeRemaining($assignment);

        // Si pas de limite de temps, l'examen n'expire jamais
        if ($timeRemaining === null) {
            return false;
        }

        return $timeRemaining <= 0;
    }

    /**
     * Obtient la durée totale de l'examen en minutes
     *
     * @param Exam $exam
     * @return int
     */
    public function getExamDuration(Exam $exam): int
    {
        // Traiter 0 comme "pas de durée définie" et retourner 60 par défaut
        return $exam->duration > 0 ? $exam->duration : 60;
    }

    /**
     * Calcule le pourcentage de temps écoulé
     *
     * @param ExamAssignment $assignment
     * @return float Pourcentage entre 0 et 100
     */
    public function getTimeElapsedPercentage(ExamAssignment $assignment): float
    {
        if (!$assignment->started_at) {
            return 0.0;
        }

        $exam = $assignment->exam;
        if (!$exam->duration || $exam->duration <= 0) {
            return 0.0;
        }

        $totalDuration = $this->getExamDuration($exam);
        $elapsed = $assignment->started_at->diffInMinutes(Carbon::now());

        $percentage = ($elapsed / $totalDuration) * 100;

        return min(100.0, max(0.0, $percentage));
    }

    /**
     * Vérifie si l'examen est proche de l'expiration (< 10% du temps restant)
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function isNearExpiration(ExamAssignment $assignment): bool
    {
        $timeRemaining = $this->getTimeRemaining($assignment);

        if ($timeRemaining === null) {
            return false;
        }

        $exam = $assignment->exam;
        $totalDuration = $this->getExamDuration($exam);

        // Moins de 10% du temps total restant
        $threshold = ($totalDuration * 0.1) * 60; // Convertir en secondes

        return $timeRemaining > 0 && $timeRemaining <= $threshold;
    }

    /**
     * Formatte le temps restant en format lisible (HH:MM:SS)
     *
     * @param ExamAssignment $assignment
     * @return string|null
     */
    public function formatTimeRemaining(ExamAssignment $assignment): ?string
    {
        $secondsRemaining = $this->getTimeRemaining($assignment);

        if ($secondsRemaining === null) {
            return null;
        }

        if ($secondsRemaining <= 0) {
            return '00:00:00';
        }

        $hours = floor($secondsRemaining / 3600);
        $minutes = floor(($secondsRemaining % 3600) / 60);
        $seconds = $secondsRemaining % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
