# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### À venir
- Notifications en temps réel (WebSockets)
- Export PDF des examens et résultats
- API REST pour intégrations tierces
- Analytics avancés pour enseignants
- Mode hors-ligne pour étudiants

---

## [1.0.0] - 2025-10-15

### Ajouté

#### Système d'assignation d'examens
- Assignation d'examens par groupes (Many-to-Many)
- Assignation individuelle d'examens aux étudiants
- Système de confirmation avant assignation
- Création automatique d'ExamAssignment au démarrage de l'examen
- Modal de confirmation personnalisée avec détails de l'examen

#### Gestion des groupes
- Architecture Many-to-Many entre exams et groups
- Pivot table `exam_group` avec traçabilité (assigned_by, assigned_at)
- Relation groups ↔ users (group_user)
- Calcul automatique des étudiants actifs par groupe
- Support des niveaux (levels) et années académiques

#### Interface utilisateur
- DataTables avec sélection bulk
- Pagination, filtres et recherche intégrés
- Composants réutilisables (Button, Modal, Section, DataTable)
- Modal de confirmation personnalisée
- Design responsive et mobile-first
- Support du dark mode
- Animations fluides avec Tailwind CSS
- Notifications toast en temps réel

#### Tests
- Tests E2E avec Playwright
- Tests frontend avec Jest
- Tests backend avec PHPUnit
- Coverage minimum de 70%
- Configuration CI/CD GitHub Actions
- Configuration CI/CD GitLab CI

#### Documentation
- README complet et professionnel
- Documentation CI/CD détaillée
- Documentation du système exam-group
- Guide de contribution
- Rapport de validation des tests

#### Services et architecture
- ExamGroupService pour la logique métier
- ExamService avec support des groupes
- ExamSessionService pour la gestion des sessions
- Architecture orientée services
- Policies pour l'autorisation
- Form Requests pour la validation

### Modifié

#### Framework et dépendances
- Migration de Laravel 11 vers Laravel 12
- Mise à jour de React vers la version 18.x
- Mise à jour de TypeScript vers la version 5.x
- Mise à jour de Vite vers la version 5.x
- Mise à jour de Inertia.js vers la version 2.x

#### Système d'assignation
- Refonte complète du système d'assignation d'examens
- Passage d'une logique individuelle à une logique par groupes
- Amélioration des performances avec eager loading
- Optimisation des requêtes SQL

#### Interface DataTables
- Amélioration des performances DataTables
- Ajout de la sélection bulk
- Ajout des actions groupées
- Amélioration de la pagination

#### Base de données
- Optimisation des index
- Ajout de contraintes UNIQUE sur exam_group
- Ajout de foreign keys avec cascade
- Migration des données existantes

### Corrigé

#### Requêtes SQL
- Correction de l'erreur SQL `orderBy('name')` dans la table groups
- Utilisation de `orderBy('academic_year', 'desc')` à la place
- Ajout de `with('level')` pour charger la relation

#### Gestion des groupes
- Correction de la gestion des groupes sans étudiants actifs
- Correction du calcul du nombre d'étudiants actifs
- Correction de l'affichage des groupes vides

#### Interface utilisateur
- Correction des problèmes de sélection dans DataTable
- Correction de l'affichage des modales
- Correction du responsive sur mobile

#### Tests
- Correction des tests Playwright
- Correction de la configuration auth.setup.ts
- Correction des timeouts dans les tests E2E

### Sécurité

- CSRF Protection activée sur tous les formulaires
- XSS Prevention avec React et escaping
- SQL Injection Prevention avec Eloquent ORM
- Password Hashing avec bcrypt
- Rate Limiting sur les routes API
- Authorization Policies avec Spatie Permission
- Input Validation avec Form Requests
- Secure Headers via middleware

### Supprimé

- Suppression de l'ancien système d'assignation individuelle uniquement
- Suppression des migrations obsolètes
- Suppression du code mort (unused code)
- Nettoyage des dépendances non utilisées

### Performance

- Cache des requêtes fréquentes
- Eager loading des relations
- Optimisation des index de base de données
- Minification des assets frontend
- Lazy loading des composants React
- Compression des images

### Dépendances

#### Backend
- PHP >= 8.4
- Laravel 12.x
- Spatie Laravel Permission 6.21
- Inertia Laravel 2.x
- Laravel Lang Common 6.7
- Ziggy 2.6

#### Frontend
- Node.js >= 20.x
- React 18.x
- TypeScript 5.x
- Vite 5.x
- Tailwind CSS 3.x
- Heroicons

#### Testing
- PHPUnit 11.5
- Playwright 1.40
- Jest 29.x
- React Testing Library

---

## [0.1.0] - 2025-09-01

### Ajouté

- Configuration initiale du projet
- Authentification de base (Laravel Breeze)
- Modèles de base (User, Exam, Question, Choice)
- Routes de base
- Migrations initiales
- Seeders de test

### Modifié

- Configuration de l'environnement de développement

---

## Convention de versioning

- **MAJOR** : Changements incompatibles avec l'API
- **MINOR** : Ajout de fonctionnalités rétrocompatibles
- **PATCH** : Corrections de bugs rétrocompatibles

## Types de changements

- **Ajouté** : Nouvelles fonctionnalités
- **Modifié** : Changements dans les fonctionnalités existantes
- **Corrigé** : Corrections de bugs
- **Supprimé** : Fonctionnalités supprimées
- **Sécurité** : Corrections de vulnérabilités
- **Performance** : Améliorations de performance
- **Dépendances** : Mises à jour de dépendances
- **Documentation** : Changements dans la documentation
