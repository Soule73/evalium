<?php

namespace App\Services\Core\Scoring;

use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Collection;

/**
 * Service central de calcul de score
 * 
 * Ce service orchestre les différentes stratégies de scoring selon le type de question.
 * Il élimine les duplications de logique présentes dans ExamService, ExamSessionService
 * et ExamScoringService.
 * 
 * Design Pattern: Strategy Pattern
 */
class ScoringService
{
    /**
     * @var array<ScoringStrategyInterface>
     */
    private array $strategies;

    /**
     * Constructeur - Enregistre toutes les stratégies disponibles
     */
    public function __construct()
    {
        $this->strategies = [
            new OneChoiceScoringStrategy(),
            new MultipleChoiceScoringStrategy(),
            new BooleanScoringStrategy(),
            new TextQuestionScoringStrategy(),
        ];
    }

    /**
     * Calcule le score total pour une assignation d'examen
     * 
     * Parcourt toutes les questions et calcule le score pour chacune
     *
     * @param ExamAssignment $assignment L'assignation à évaluer
     * @return float Le score total obtenu
     */
    public function calculateAssignmentScore(ExamAssignment $assignment): float
    {
        $totalScore = 0.0;

        // Charger l'examen avec ses questions
        $exam = $assignment->exam()->with('questions.choices')->first();

        foreach ($exam->questions as $question) {
            $totalScore += $this->calculateQuestionScore($assignment, $question);
        }

        return round($totalScore, 2);
    }

    /**
     * Calcule le score pour une question spécifique
     *
     * @param ExamAssignment $assignment L'assignation
     * @param Question $question La question à évaluer
     * @return float Le score obtenu pour cette question
     */
    public function calculateQuestionScore(ExamAssignment $assignment, Question $question): float
    {
        // Récupérer les réponses de l'étudiant pour cette question
        $answers = $assignment->answers()
            ->where('question_id', $question->id)
            ->with('choice')
            ->get();

        // Trouver la stratégie appropriée
        $strategy = $this->getStrategyForQuestionType($question->type);

        if (!$strategy) {
            // Type de question non supporté
            return 0.0;
        }

        // Calculer le score via la stratégie
        return $strategy->calculateScore($question, $answers);
    }

    /**
     * Vérifie si une réponse est correcte
     *
     * @param Question $question La question
     * @param Collection $answers Les réponses données
     * @return bool
     */
    public function isAnswerCorrect(Question $question, Collection $answers): bool
    {
        $strategy = $this->getStrategyForQuestionType($question->type);

        if (!$strategy) {
            return false;
        }

        return $strategy->isCorrect($question, $answers);
    }

    /**
     * Calcule uniquement le score auto-corrigeable (QCM, boolean)
     * Exclut les questions textuelles qui nécessitent une correction manuelle
     *
     * @param ExamAssignment $assignment
     * @return float
     */
    public function calculateAutoCorrectableScore(ExamAssignment $assignment): float
    {
        $totalScore = 0.0;

        // Types de questions auto-corrigeables
        $autoCorrectableTypes = ['one_choice', 'multiple', 'boolean'];

        $exam = $assignment->exam()->with('questions.choices')->first();

        foreach ($exam->questions as $question) {
            if (in_array($question->type, $autoCorrectableTypes)) {
                $totalScore += $this->calculateQuestionScore($assignment, $question);
            }
        }

        return round($totalScore, 2);
    }

    /**
     * Vérifie si un examen contient des questions nécessitant une correction manuelle
     *
     * @param ExamAssignment $assignment
     * @return bool
     */
    public function hasManualCorrectionQuestions(ExamAssignment $assignment): bool
    {
        return $assignment->exam->questions()
            ->whereIn('type', ['text', 'essay'])
            ->exists();
    }

    /**
     * Récupère la stratégie appropriée pour un type de question
     *
     * @param string $questionType
     * @return ScoringStrategyInterface|null
     */
    private function getStrategyForQuestionType(string $questionType): ?ScoringStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($questionType)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Obtient la liste de toutes les stratégies enregistrées
     *
     * @return array<ScoringStrategyInterface>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Ajoute une stratégie personnalisée
     * Utile pour ajouter de nouveaux types de questions sans modifier ce fichier
     *
     * @param ScoringStrategyInterface $strategy
     * @return self
     */
    public function addStrategy(ScoringStrategyInterface $strategy): self
    {
        $this->strategies[] = $strategy;
        return $this;
    }
}
