# Question Validation Strategy Pattern

## 📋 Vue d'ensemble

Ce répertoire contient l'implémentation du **Strategy Pattern** pour la validation des questions d'examen. Cette architecture permet une validation flexible, maintenable et extensible en séparant la logique de validation pour chaque type de question.

## 🎯 Objectifs

- **Séparation des responsabilités** : Chaque type de question a sa propre stratégie de validation
- **Extensibilité** : Facile d'ajouter de nouveaux types de questions sans modifier le code existant
- **Maintenabilité** : Code plus propre, organisé et testable
- **Réutilisabilité** : Les stratégies peuvent être utilisées dans différents contextes

## 📁 Structure des fichiers

```
app/Strategies/Validation/
├── QuestionValidationStrategy.php           # Interface définissant le contrat
├── QuestionValidationContext.php            # Contexte/Factory qui gère les stratégies
├── MultipleChoiceValidationStrategy.php     # Stratégie pour questions à choix multiples
├── SingleChoiceValidationStrategy.php       # Stratégie pour questions à choix unique/boolean
└── TextQuestionValidationStrategy.php       # Stratégie pour questions de type texte
```

## 🔧 Comment ça fonctionne

### 1. Interface `QuestionValidationStrategy`

Définit le contrat que toutes les stratégies doivent respecter :

```php
interface QuestionValidationStrategy
{
    public function validate(Validator $validator, array $question, int $index): void;
    public function supports(string $questionType): bool;
}
```

### 2. Stratégies concrètes

Chaque stratégie implémente la logique de validation spécifique à un type de question :

#### **MultipleChoiceValidationStrategy**
- Vérifie qu'il y a au moins 2 choix
- Vérifie qu'au moins 2 choix sont marqués comme corrects

#### **SingleChoiceValidationStrategy**
- Vérifie qu'il y a au moins 2 choix
- Vérifie qu'exactement 1 choix est marqué comme correct
- Supporte les types `one_choice` et `boolean`

#### **TextQuestionValidationStrategy**
- Pas de validation supplémentaire (questions de texte libre)
- Inclus pour la complétude et l'extensibilité future

### 3. Contexte `QuestionValidationContext`

Agit comme un **Factory** et un **Facade** :
- Enregistre toutes les stratégies disponibles
- Sélectionne la stratégie appropriée pour un type de question
- Délègue la validation à la stratégie sélectionnée

## 💡 Utilisation

### Dans les Form Requests

```php
use App\Strategies\Validation\QuestionValidationContext;

public function withValidator(Validator $validator): void
{
    $validator->after(function ($validator) {
        $data = $validator->getData();
        $questions = $data['questions'] ?? [];

        // Utilise le Strategy Pattern pour valider les questions
        $validationContext = new QuestionValidationContext();
        $validationContext->validateQuestions($validator, $questions);
    });
}
```

## ➕ Ajouter un nouveau type de question

Pour ajouter un nouveau type de question (ex: `rating`, `file_upload`), suivez ces étapes :

### 1. Créer une nouvelle stratégie

```php
<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

class RatingQuestionValidationStrategy implements QuestionValidationStrategy
{
    public function validate(Validator $validator, array $question, int $index): void
    {
        // Logique de validation spécifique aux questions de notation
        if (!isset($question['min_rating']) || !isset($question['max_rating'])) {
            $validator->errors()->add(
                "questions.{$index}.rating",
                "Les valeurs min et max sont requises pour les questions de notation."
            );
        }
    }

    public function supports(string $questionType): bool
    {
        return $questionType === 'rating';
    }
}
```

### 2. Enregistrer la stratégie

Dans `QuestionValidationContext::registerDefaultStrategies()` :

```php
private function registerDefaultStrategies(): void
{
    $this->registerStrategy(new MultipleChoiceValidationStrategy());
    $this->registerStrategy(new SingleChoiceValidationStrategy());
    $this->registerStrategy(new TextQuestionValidationStrategy());
    $this->registerStrategy(new RatingQuestionValidationStrategy()); // ✨ Nouvelle stratégie
}
```

**C'est tout !** Aucune modification nécessaire dans les Form Requests existants.

## 🧪 Tests

Chaque stratégie peut être testée indépendamment :

```php
public function test_multiple_choice_validates_minimum_correct_answers()
{
    $strategy = new MultipleChoiceValidationStrategy();
    $validator = Validator::make([], []);
    
    $question = [
        'type' => 'multiple',
        'choices' => [
            ['is_correct' => true],
            ['is_correct' => false]
        ]
    ];
    
    $strategy->validate($validator, $question, 0);
    
    $this->assertTrue($validator->errors()->has('questions.0.choices'));
}
```

## 📊 Diagramme UML

```
┌─────────────────────────────────┐
│  QuestionValidationStrategy     │
│  <<interface>>                  │
├─────────────────────────────────┤
│ + validate(...)                 │
│ + supports(string): bool        │
└─────────────────────────────────┘
           △
           │ implements
           │
    ┌──────┴──────┬──────────────┬─────────────────┐
    │             │              │                 │
┌───┴───────┐ ┌───┴───────┐ ┌───┴───────┐ ┌──────┴──────┐
│ Multiple  │ │  Single   │ │   Text    │ │   Future    │
│  Choice   │ │  Choice   │ │  Question │ │  Strategies │
│ Strategy  │ │ Strategy  │ │ Strategy  │ │    ...      │
└───────────┘ └───────────┘ └───────────┘ └─────────────┘
       △           △             △
       └───────────┴─────────────┴──────────────┐
                                                 │
                                    ┌────────────┴─────────────┐
                                    │ QuestionValidation       │
                                    │ Context                  │
                                    ├──────────────────────────┤
                                    │ - strategies[]           │
                                    ├──────────────────────────┤
                                    │ + registerStrategy(...)  │
                                    │ + validateQuestion(...)  │
                                    │ + validateQuestions(...) │
                                    └──────────────────────────┘
```

## ✅ Avantages de cette implémentation

1. **Open/Closed Principle** : Ouvert à l'extension, fermé à la modification
2. **Single Responsibility** : Chaque classe a une seule responsabilité
3. **Dependency Inversion** : Dépend des abstractions, pas des implémentations concrètes
4. **Testabilité** : Chaque stratégie peut être testée indépendamment
5. **Lisibilité** : Code plus clair et auto-documenté
6. **Réutilisabilité** : Stratégies réutilisables dans d'autres contextes

## 📝 Notes techniques

- Les stratégies sont **stateless** : pas d'état partagé entre les validations
- Le contexte est **léger** : création peu coûteuse à chaque validation
- Les messages d'erreur sont **internationalisés** via `__()`
- Compatible avec le système de validation Laravel existant

## 🔗 Références

- [Design Patterns: Strategy](https://refactoring.guru/design-patterns/strategy)
- [Laravel Validation Documentation](https://laravel.com/docs/validation)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
