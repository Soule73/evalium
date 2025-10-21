# 📚 Examena - Plateforme de Gestion d'Examens en Ligne

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)]()
[![Coverage](https://img.shields.io/badge/coverage-70%25-green.svg)]()
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?logo=react)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?logo=typescript)](https://www.typescriptlang.org/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Une application web pour la gestion d'examens en ligne, développée avec **Laravel 12**, **React 18**, **TypeScript** et **Inertia.js**.

---

## Fonctionnalités

### Administrateurs
- Gestion des utilisateurs et groupes
- Attribution bulk des étudiants
- Système de permissions (Spatie)
- Statistiques en temps réel
- DataTables avancés

### Enseignants
- Création d'examens avec timer
- Questions à choix multiples
- Assignation par groupes ou individuelle
- Confirmation avant assignation
- Visualisation des résultats
- Correction automatique

### Étudiants
- Accès aux examens via groupes
- Interface intuitive avec timer
- Sauvegarde automatique
- Consultation des résultats
- Historique complet

### Interface
- Design responsive mobile-first
- Dark mode
- Animations Tailwind CSS
- Sélection bulk
- Modales personnalisées
- Notifications temps réel

---

## Stack Technique

### Backend
- Laravel 12.x | PHP 8.4+
- MySQL 8.0
- Spatie Permission
- Architecture Services

### Frontend
- React 18.x | TypeScript 5.x
- Inertia.js 2.x
- Tailwind CSS 3.x
- Vite 5.x

### Testing
- PHPUnit 11.x
- Jest + React Testing Library
- Playwright
- Coverage: 70%+

### DevOps
- GitHub Actions / GitLab CI
- Laravel Pint / PHPStan
- Docker Sail (optionnel)

---

## Prérequis

- PHP >= 8.4
- Composer >= 2.x
- Node.js >= 20.x
- MySQL >= 8.0
- Git

---

## Installation

### Cloner le repository
```bash
git clone https://github.com/Soule73/examena.git
cd examena
```

### Installer les dépendances
```bash
composer install
npm install
```

### Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### Base de données
```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE examena CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Exécuter les migrations
php artisan migrate

# Données de test (optionnel)
php artisan db:seed
```

### Lancer l'application
```bash
# Terminal 1 - Backend
php artisan serve

# Terminal 2 - Frontend (dev)
npm run dev
```

**Accès** : http://localhost:8000

---

## Comptes de test

Après avoir exécuté `php artisan db:seed`:

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@example.com | password123 |
| Enseignant | teacher@example.com | password123 |
| Étudiant | student@example.com | password123 |

---

## Tests

### Backend (PHPUnit)
```bash
# Tous les tests
php artisan test                    
# Avec coverage
php artisan test --coverage         
```

### Frontend (Jest)
```bash
# Tests unitaires
npm run test:unit                   
# Mode watch
npm run test:unit:watch             
```

### E2E (Playwright)
```bash
# Tests E2E
npm run test:e2e                    
# Mode UI
npm run test:e2e:ui                 
# Voir rapport
npm run test:e2e:report             
```

---

## Structure du projet

```
examena/
├── app/
│   ├── Console/           # Commandes Artisan
│   ├── Helpers/           # Helper functions
│   ├── Http/
│   │   ├── Controllers/   # Admin, Teacher, Student
│   │   ├── Middleware/    # Custom middleware
│   │   └── Requests/      # Form requests
│   ├── Models/            # Eloquent models
│   ├── Policies/          # Authorization policies
│   └── Services/          # Business logic
│       ├── Admin/
│       ├── Teacher/
│       └── Student/
├── database/
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/          # Database seeders
├── resources/
│   ├── css/              # Styles
│   ├── ts/               # TypeScript/React
│   │   ├── Components/   # React components
│   │   ├── Layouts/      # Page layouts
│   │   ├── Pages/        # Inertia pages
│   │   └── types/        # TypeScript types
│   └── views/            # Blade templates
├── routes/
│   ├── web.php           # Routes web
│   └── console.php       # Artisan commands
├── tests/
│   ├── e2e/              # Playwright tests
│   ├── Feature/          # Laravel feature tests
│   ├── Unit/             # Laravel unit tests
│   └── frontend/         # Jest tests
├── .github/
│   └── workflows/        # GitHub Actions
├── .gitlab-ci.yml        # GitLab CI
└── playwright.config.ts  # Playwright config
```

---

## Fonctionnalités clés

### Système d'assignation d'examens
- Assignation d'examens par groupes (Many-to-Many)
- Assignation individuelle aux étudiants
- Système de confirmation avant assignation
- Création automatique d'ExamAssignment au démarrage de l'examen

### DataTables avancés
- Sélection bulk (multiple)
- Pagination dynamique
- Filtres et recherche en temps réel
- Actions groupées personnalisables

### Sécurité
- Protection CSRF (Laravel)
- Prévention XSS (React + escaping)
- Prévention injection SQL (Eloquent ORM)
- Hashage des mots de passe (bcrypt)
- Rate Limiting (API throttle)
- Policies d'autorisation (Spatie)
- Validation des entrées (Form Requests)
- Headers sécurisés (middleware)

---

## Documentation

- [CHANGELOG.md](CHANGELOG.md) - Historique des versions et modifications
- [CI_CD_DOCUMENTATION.md](CI_CD_DOCUMENTATION.md) - Guide CI/CD complet
---

## Contribution

Les contributions sont les bienvenues ! Voici comment contribuer :

1. **Fork** le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une **Pull Request**

### Guidelines

- Suivre le style de code (Laravel Pint, ESLint)
- Écrire des tests pour les nouvelles fonctionnalités
- Maintenir la couverture >= 70%
- Documenter les nouvelles fonctionnalités
- Respecter les conventions de commit
- Tester localement avant de pusher

---

## Rapporter un bug

Ouvrir une [issue](https://github.com/Soule73/examena/issues/new) avec :

- Description claire du problème
- Étapes pour reproduire
- Comportement attendu
- Comportement actuel
- Screenshots si applicable
- Environnement (OS, PHP, Node version)

---

## Roadmap

- [ ] Notifications en temps réel (WebSockets)
- [ ] Export PDF des examens et résultats
- [ ] API REST pour intégrations tierces
- [ ] Analytics avancés pour enseignants
- [ ] Mode hors-ligne pour étudiants

Voir le [CHANGELOG.md](CHANGELOG.md) pour l'historique complet des versions.

---

## Auteur

**Soule73**
- GitHub: [@Soule73](https://github.com/Soule73)

---

## Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## Remerciements

- [Laravel](https://laravel.com) - Framework PHP
- [React](https://react.dev) - Librairie UI
- [Inertia.js](https://inertiajs.com) - Adaptateur Laravel/React
- [Tailwind CSS](https://tailwindcss.com) - Framework CSS
- [Playwright](https://playwright.dev) - Testing E2E
- [Spatie](https://spatie.be) - Packages Laravel

---

## Support

- Email: support@examena.com
- Discussions: [GitHub Discussions](https://github.com/Soule73/examena/discussions)
- Issues: [GitHub Issues](https://github.com/Soule73/examena/issues)
- Wiki: [Documentation](https://github.com/Soule73/examena/wiki)

---

<div align="center">

**Si ce projet vous est utile, n'hésitez pas à lui donner une étoile ! ⭐**

Made with ❤️ by [Soule73](https://github.com/Soule73)

</div>