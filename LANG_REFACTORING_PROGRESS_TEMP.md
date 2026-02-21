# Lang Refactoring — Document de progression

> **Créé le :** 21 février 2026  
> **Branche :** `develop/v1.1-improvements`  
> **Statut global :** Phase 0 — Audit terminé, en attente de démarrage Phase 1

---

## 1. Contexte et objectifs

### Problème identifié
- **2 615 valeurs** définies côté PHP (EN seul), **×2 pour FR** = ~5 230 valeurs totales envoyées au frontend
- **~1 000 clés réellement utilisées** côté TypeScript/React
- Taux d'orphelins estimé : **~60 %** des clés définies ne sont jamais utilisées côté frontend
- Des valeurs identiques sont répétées dans **jusqu'à 28 fichiers différents** (`Status` : 28×, `Actions` : 14×, `Teacher`/`Student`/`Class` : 13–16×)

### Objectifs
1. **Éliminer les duplications** inter-fichiers via un dossier `commons/`
2. **Supprimer les orphelins** (clés définies mais jamais utilisées)
3. **Réduire la taille du payload** envoyé au frontend par `laravel-react-i18n`
4. **Migration progressive** sans casser aucune traduction existante

### Ce qu'on ne touche PAS
Ces fichiers sont utilisés exclusivement par le **backend Laravel**, ils sont hors scope :
- `validation.php` (307 valeurs — validateur Laravel)
- `http-statuses.php` (78 valeurs — réponses HTTP)
- `auth.php` / `passwords.php` — authentification Laravel standard
- `messages.php` — flash messages backend (quelques clés frontend seulement)
- `notifications.php` — templates email (juste refactorisé)

---

## 2. Audit des fichiers de traduction

### 2.1 Taille et usage frontend (EN)

| Fichier | Valeurs définies | Clés utilisées (TS) | Orphelins estimés | Priorité |
|---|---|---|---|---|
| `admin_pages.php` | 575 | 343 | ~232 | 🔴 Haute |
| `components.php` | 288 | 164 | ~124 | 🔴 Haute |
| `validation.php` | 307 | 0 (backend) | — | ⬜ Hors scope |
| `assessment_pages.php` | 104 | 38 | ~66 | 🟠 Moyenne |
| `student_assessment_pages.php` | 153 | 119 | ~34 | 🟠 Moyenne |
| `messages.php` | 164 | 4 | ~160 | 🟡 Basse |
| `actions.php` | 109 | 0 (non utilisé TS) | ~109 | 🟡 À décider |
| `teacher_pages.php` | 95 | 0* | ~95 | 🟡 Basse |
| `formatters.php` | 48 | 20 | ~28 | 🟡 Basse |
| `grading_pages.php` | 51 | 29 | ~22 | 🟡 Basse |
| `landing.php` | 37 | 37 | ~0 | 🟢 OK |
| `student_enrollment_pages.php` | 58 | 36 | ~22 | 🟠 Moyenne |
| `dashboard.php` | 61 | 26 | ~35 | 🟠 Moyenne |
| `sidebar.php` | 30 | 23 | ~7 | 🟢 OK |
| `breadcrumbs.php` | 36 | 16 | ~20 | 🟡 Basse |
| `teacher_class_pages.php` | 67 | 18 | ~49 | 🟠 Moyenne |
| `auth_pages.php` | 26 | 26 | ~0 | 🟢 OK |
| `common.php` | 24 | 10 | ~14 | 🟡 À fusionner |
| `permissions.php` | 63 | 0 | ~63 | 🟡 Backend? |
| `corrections.php` | 29 | 0* | ~29 | 🟡 Basse |
| `results.php` | 36 | 0* | ~36 | 🟡 Basse |
| `users.php` | 48 | 5 | ~43 | 🟡 Basse |
| `assignments.php` | 21 | 0 | ~21 | 🔴 Orphelin total |

> \* Vérification manuelle nécessaire, la regex peut manquer les clés dynamiques (`t(\`prefix.${var}\``)

### 2.2 Duplications majeures identifiées

| Valeur dupliquée | Nb occurrences | Fichiers concernés |
|---|---|---|
| `'Status'` | 28× | Tous les fichiers *_pages.php + common + components |
| `'Assessments'` | 17× | admin_pages, assessment_pages, breadcrumbs, sidebar, teacher_* |
| `'Subject'` | 16× | Quasi tous les fichiers |
| `'Teacher'` | 16× | Idem |
| `'Class'` | 15× | Idem |
| `'Actions'` | 14× | Partout |
| `'Student'` | 14× | Partout |
| `'Questions'` | 13× | assessment_pages, components, student_*, teacher_* |
| `'Duration'` | 13× | Idem |
| `'Graded'` | 13× | admin_pages, components, dashboard, formatters, grading_pages... |
| `'In Progress'` | 11× | Partout |
| `'Cancel'` | 11× | actions, admin_pages, assessment_pages × 4, common, components × 3 |
| `'Creating...'` | 8× | admin_pages (7× interne!) + components |
| `'Updating...'` | 7× | admin_pages (7× interne!) |
| `'All Statuses'` | 7× | admin_pages × 3, components, formatters |
| `'Score'` | 10× | Partout |
| `'Not Started'` | 10× | assessment_pages × 3, components × 3, formatters, student_* |
| `'Completed'` | 10× | Partout |

---

## 3. Structure commons/ proposée

### 3.1 Principle
`lang/en/commons/ui.php` → accessible via `t('commons/ui.save')` côté frontend  
Laravel native supporte les sous-dossiers dans `lang/` nativement.

### 3.2 Fichiers à créer

```
lang/
  en/
    commons/
      ui.php          ← Actions/verbes UI + états de chargement
      status.php      ← Statuts transversaux (assessment, enrollment, user)
      entities.php    ← Entités métier (Student, Teacher, Class, Subject, Score...)
      table.php       ← Composant DataTable (Search, No results, Pagination...)
      form.php        ← Labels de formulaires mutualisés (Name, Email, Description...)
  fr/
    commons/
      ui.php
      status.php
      entities.php
      table.php
      form.php
```

### 3.3 Contenu prévu par fichier

#### `commons/ui.php`
```
Actions verbes : create, edit, delete, view, save, cancel, back, close, confirm,
                 update, reset, search, archive, transfer, withdraw, restore,
                 duplicate, send, submit
États chargement : creating, updating, saving, loading, submitting, processing
Boutons contextuels : add, remove, apply, clear,next, previous
```

#### `commons/status.php`
```
Statuts assessment : not_started, in_progress, completed, submitted, graded,
                     published, draft, archived
Statuts enrollment : active, withdrawn, transferred
Statuts user : active, inactive, deleted
Filtres : all_statuses, all_roles, all_classes, all_subjects, all_years
```

#### `commons/entities.php`
```
Noms entités (singulier/pluriel) : student/students, teacher/teachers,
class/classes, subject/subjects, assessment/assessments, level/levels,
academic_year, semester, score, duration, type, coefficient
```

#### `commons/table.php`
```
DataTable : search_placeholder, no_results, no_results_subtitle, actions,
            clear_search, items_selected (pluralisation), loading,
            empty_state labels
Pagination : page_of, previous, next, per_page
```

#### `commons/form.php`
```
Labels : name, email, description, type, status, date, start_date, end_date,
         created_at, capacity, coefficient, password, role
Placeholders : enter_name, enter_email, select_role, select_class, select_subject
Actions form : required_field, optional
```

---

## 4. Plan de migration par phases

### Phase 0 — Audit ✅ TERMINÉ
- [x] Compter les fichiers et valeurs (2 615 EN, ~5 230 total)
- [x] Identifier les clés utilisées côté TS (1 059 uniques détectées)
- [x] Identifier les duplications (top 18 valeurs dupliquées documentées)
- [x] Définir la structure `commons/`
- [x] Créer ce document de tracking

---

### Phase 1 — Créer les fichiers `commons/` 🔲 À FAIRE
**Durée estimée :** 1–2h  
**Risque :** Zéro (création seule, aucun fichier existant modifié)

- [ ] Créer `lang/en/commons/ui.php`
- [ ] Créer `lang/fr/commons/ui.php`
- [ ] Créer `lang/en/commons/status.php`
- [ ] Créer `lang/fr/commons/status.php`
- [ ] Créer `lang/en/commons/entities.php`
- [ ] Créer `lang/fr/commons/entities.php`
- [ ] Créer `lang/en/commons/table.php`
- [ ] Créer `lang/fr/commons/table.php`
- [ ] Créer `lang/en/commons/form.php`
- [ ] Créer `lang/fr/commons/form.php`
- [ ] Vérifier que `t('commons/ui.save')` résout bien côté frontend (test rapide)

---

### Phase 2 — Migration `admin_pages.php` 🔲 À FAIRE
**Fichier le plus impacté :** 575 valeurs, gain estimé ~140 valeurs supprimées  
**Fichiers TS à mettre à jour :** Pages et composants Admin

#### Étape 2a — Remplacer les doublons internes (adminpages vs adminpages)
- [ ] Remplacer les 7× `'creating'` internes par référence à `commons/ui.creating`
- [ ] Remplacer les 7× `'updating'` internes par référence à `commons/ui.updating`
- [ ] Remplacer les 5× `'cancel'` internes
- [ ] Remplacer les 4× `'status'` internes
- [ ] Remplacer les 4× `'all_statuses'` internes

#### Étape 2b — Remplacer dans les fichiers TS Admin
- [ ] `Pages/Admin/**/*.tsx` : substituer les clés communes
- [ ] `Components/features/enrollments/**/*.tsx`
- [ ] `Components/features/classes/**/*.tsx`
- [ ] `Components/features/users/**/*.tsx`

#### Étape 2c — Supprimer les clés migrées de `admin_pages.php`
- [ ] Valider qu'aucun TS n'utilise plus les anciennes clés
- [ ] Supprimer de `admin_pages.php`

#### Étape 2d — Supprimer les orphelins détectés
- [ ] Lister les clés `admin_pages.*` jamais utilisées
- [ ] Confirmer qu'elles ne sont pas dans des clés dynamiques
- [ ] Supprimer

---

### Phase 3 — Migration `components.php` 🔲 À FAIRE
**288 valeurs définies → 164 utilisées**  
**Gain estimé :** ~60 valeurs migrées vers commons

- [ ] Identifier toutes les clés `components.*` doublonnant avec `commons/`
- [ ] Mettre à jour `Components/shared/**/*.tsx`
- [ ] Mettre à jour `Components/ui/**/*.tsx`
- [ ] Supprimer les clés communes de `components.php`
- [ ] Supprimer les orphelins de `components.php`

---

### Phase 4 — Migration des fichiers pages restants 🔲 À FAIRE
Ordre par priorité (nb d'orphelins estimé) :

- [ ] `assessment_pages.php` (104 → ~38 utilisées)
- [ ] `teacher_class_pages.php` (67 → ~18 utilisées)
- [ ] `student_enrollment_pages.php` (58 → ~36 utilisées)
- [ ] `dashboard.php` (61 → ~26 utilisées)
- [ ] `student_assessment_pages.php` (153 → ~119 utilisées)
- [ ] `grading_pages.php` (51 → ~29 utilisées)
- [ ] `formatters.php` (48 → ~20 utilisées)
- [ ] `teacher_pages.php` (95 → ~0 détectées — vérifier clés dynamiques)
- [ ] `breadcrumbs.php` (36 → ~16 utilisées)
- [ ] `users.php` (48 → ~5 utilisées)
- [ ] `corrections.php` (29 → ~0 détectées)
- [ ] `results.php` (36 → ~0 détectées)

---

### Phase 5 — Nettoyage final 🔲 À FAIRE

- [ ] Fusionner `common.php` (24 valeurs) dans `commons/ui.php` et supprimer `common.php`
- [ ] Décider du sort de `actions.php` (109 valeurs, 0 usage TS détecté)
  - Option A : supprimer → risque si backend l'utilise
  - Option B : garder pour backend uniquement, annoter
- [ ] Décider du sort de `assignments.php` (21 valeurs, 0 usage)
- [ ] Décider du sort de `permissions.php` (63 valeurs, 0 usage TS)
- [ ] Vérifier `messages.php` — séparer clés backend/frontend, garder uniquement backend
- [ ] Run final : `php artisan test` pour vérifier que rien n'est cassé
- [ ] Benchmark payload avant/après (comparer taille JSON envoyée au frontend)

---

## 5. Règles de migration (à respecter à chaque étape)

1. **Toujours lire le fichier TS avant d'éditer le PHP** — vérifier la clé exacte utilisée
2. **Ne jamais supprimer une clé PHP sans avoir d'abord mis à jour TOUS les usages TS** correspondants
3. **Tester après chaque fichier TS migré** — `npm run build` ou vérification visuelle
4. **Les clés dynamiques** (`t(\`prefix.${variable}\`)`) nécessitent une vérification manuelle — la regex ne les détecte pas
5. **Committer après chaque Phase** pour avoir des points de retour propres
6. **Ne pas modifier `validation.php`, `http-statuses.php`, `auth.php`, `passwords.php`** — backend only

---

## 6. Métriques de suivi

| Métrique | Avant refactoring | Cible | Actuel |
|---|---|---|---|
| Valeurs totales EN | 2 615 | < 1 400 | 2 615 |
| Fichiers lang EN | 31 | ~28 (+ 5 commons) | 31 |
| Orphelins estimés | ~1 600 | < 100 | ~1 600 |
| Lignes `admin_pages.php` | 624 | < 400 | 624 |
| Lignes `components.php` | 369 | < 220 | 369 |
| Tests backend | 599 ✅ | 599+ ✅ | 599 |

---

## 7. Journal des changements

| Date | Phase | Action | Commit |
|---|---|---|---|
| 2026-02-21 | Phase 0 | Audit complet + création du document de tracking | — |

---

## 8. Notes et décisions techniques

### Accès aux fichiers `commons/` côté frontend
Laravel `laravel-react-i18n` charge tous les fichiers du dossier `lang/{locale}/` **récursivement**.  
Un fichier `lang/en/commons/ui.php` sera accessible via `t('commons/ui.save')`.  
→ **Format de clé :** `t('commons/nomfichier.cle')` (avec `/` comme séparateur de dossier)

### Clés dynamiques — attention particulière
Certaines clés sont construites dynamiquement en TS, ex :
```tsx
t(`admin_pages.enrollments.status_${item.status}`)
t(`formatters.assessment_type.${type}`)
```
Ces clés **ne sont pas détectées par la regex** `t('...')`. Avant de supprimer un groupe de clés, toujours vérifier avec :
```powershell
grep -r "admin_pages.enrollments.status" resources/ts --include="*.tsx"
```

### `actions.php` — décision en suspens
Ce fichier (109 valeurs) ressemble à un fichier générique issu d'un starter kit.  
Aucune clé `actions.*` n'est utilisée côté TS. À investiguer côté backend avant suppression.

### `messages.php` — backend flash messages
Ce fichier est utilisé par le backend via `__('messages.user_created')` etc.  
Les ~4 clés détectées côté TS sont probablement des faux positifs ou des clés partagées.  
→ **Ne pas modifier sans vérification backend** via `grep -r "messages\." app/ --include="*.php"`
