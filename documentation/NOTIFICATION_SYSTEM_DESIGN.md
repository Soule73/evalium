# Notification System — Design & Decision Record

**Status:** En discussion  
**Date:** 22 février 2026  
**Contexte:** Le système ne dispose actuellement d'aucune notification in-app. Seul `UserCredentialsNotification` existe (canal `mail` uniquement, envié lors de la création d'un compte).

---

## 1. État actuel du codebase

### Ce qui existe

| Élément | Fichier | Notes |
|---|---|---|
| `UserCredentialsNotification` | `app/Notifications/` | Email uniquement, `ShouldQueue` |
| Flash messages | `HandleInertiaRequests::share()` | Session-based, éphémères |
| `FlashToastHandler` | `resources/ts/Components/` | Consomme les flash côté front |
| `headerActions` slot | `AuthenticatedLayout.tsx` | Point d'injection pour une cloche |
| `student.assessments.save-answers` | Scheduler toutes les 5/30 min | Jobs schedulés déjà fonctionnels |
| `assessment:auto-submit-expired` | `routes/console.php` | Infrastructure scheduler opérationnelle |

### Ce qui manque

- Table `notifications` (standard Laravel)
- Modèles d'événements métier (`AssessmentPublished`, `AssessmentGraded`, etc.)
- Jobs/listeners déclenchant les notifications
- Route API pour lire/marquer les notifications
- Composant UI header (cloche + dropdown)
- Partage du compteur dans `HandleInertiaRequests`

---

## 2. Canaux de livraison retenus

### Canal `database` (in-app) — Prioritaire

Laravel possède un système de notifications sur canal `database` natif, créant une table `notifications` avec UUID, type, données JSON, et `read_at`. C'est le canal principal.

**Avantages :**
- Zéro dépendance externe (ni Pusher, ni WebSocket)
- Persistantes : l'étudiant voit ses notifications même après reconnexion
- API standardisée : `$user->unreadNotifications`, `$user->notifications()->paginate()`
- Marquage lu/non-lu natif

### Canal `mail` — Optionnel, opt-in futur

Le canal mail reste disponible (infrastructure déjà en place via `UserCredentialsNotification`). À activer par préférence utilisateur dans une V2. Exclu du scope actuel pour rester simple.

---

## 3. Types de notifications à implémenter

### Phase 1 — MVP

| Notification | Destinataire | Déclencheur | Priorité |
|---|---|---|---|
| `AssessmentPublishedNotification` | Étudiant inscrit | Publication d'une évaluation (is_published = true) | Haute |
| `AssessmentGradedNotification` | Étudiant | Enseignant saisit les notes (`graded_at` renseigné) | Haute |
| `AssessmentSubmittedNotification` | Enseignant | Étudiant soumet une évaluation | Moyenne |
| `AssessmentStartingSoonNotification` | Étudiant | J−15 min avant `scheduled_at` (supervisé) | Haute |

### Phase 2 — À planifier

| Notification | Destinataire | Déclencheur |
|---|---|---|
| `HomeworkDueSoonNotification` | Étudiant | 24h avant `due_date` (homework) |
| `EnrollmentNotification` | Étudiant | Inscription dans une classe |
| `AssessmentAssignedToClassNotification` | Enseignant | Admin assigne un assessment |

---

## 4. Architecture technique

### 4.1 Table (migration standard Laravel)

```bash
php artisan make:notification AssessmentPublishedNotification
php artisan notifications:table
php artisan migrate
```

La table `notifications` créée par Laravel contient :
```
id (uuid), type, notifiable_type, notifiable_id,
data (json), read_at (nullable timestamp), created_at, updated_at
```

### 4.2 Structure d'une notification

```php
// app/Notifications/AssessmentPublishedNotification.php
class AssessmentPublishedNotification extends Notification implements ShouldQueue
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'assessment_published',
            'assessment_id'  => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject'        => $this->assessment->class_subject?->subject?->name,
            'scheduled_at'   => $this->assessment->scheduled_at,
            'delivery_mode'  => $this->assessment->delivery_mode,
        ];
    }
}
```

**Convention `data.type`** : slug snake_case utilisé côté front pour le routage et l'icône.

### 4.3 Déclencheurs

#### Événement de publication (`AssessmentService::publishAssessment`)

```php
// Dans AssessmentService quand is_published passe à true
$enrolledStudents = $assessment->classSubject->class->activeEnrollments()->with('student')->get();
Notification::send($enrolledStudents->pluck('student'), new AssessmentPublishedNotification($assessment));
```

#### Reminders J−15 min (nouveau commande schedulée)

```php
// app/Console/Commands/SendAssessmentReminders.php
// Lancé toutes les 5 min via Schedule::command('notifications:send-reminders')
// Requête : assessments où scheduled_at entre now() et now()+15min ET is_published=true
```

#### Correction soumise (dans `ScoringService` ou le contrôleur)

```php
$assignment->student->notify(new AssessmentGradedNotification($assessment, $score));
```

### 4.4 Partage du compteur via Inertia (lazy)

Dans `HandleInertiaRequests::share()` :

```php
'notifications' => Inertia::lazy(fn () => [
    'unread_count' => $user?->unreadNotifications()->count() ?? 0,
]),
```

`Inertia::lazy()` garantit que la requête SQL n'est exécutée que lors des reloads qui demandent explicitement `only: ['notifications']` — pas à chaque chargement de page.

### 4.5 Route API dédiée

```php
// routes/web.php — dans le groupe auth
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/',         [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
});
```

`index` retourne du JSON (non-Inertia) pour être appelé via `axios` depuis le dropdown.

### 4.6 Composant frontend — `NotificationBell`

Point d'injection : dans `AuthenticatedLayout.tsx`, entre `AcademicYearSelector` et `UserMenu`.

```
AuthenticatedLayout header
└── <AcademicYearSelector />
└── <NotificationBell />   ← nouveau
└── <UserMenu />
```

**Comportement :**
- Badge rouge avec compteur (tiré de `auth.notifications.unread_count`)
- Click → ouvre un panneau **slide-over droit** (largeur ~400px, hauteur 100vh, overlay sombre derrière)
- Chargement lazy via `axios.get('/notifications')` au premier clic (pas au montage)
- Polling toutes les 60s pour rafraîchir le compteur : `router.reload({ only: ['notifications'] })`
- Notification non lue : fond légèrement coloré, point bleu
- Click sur une notification → `axios.post('/notifications/{id}/read')` + navigation vers la page concernée
- Bouton "Tout marquer comme lu" en haut du panneau
- Fermeture via bouton ✕, touche Escape, ou clic sur l'overlay

---

## 5. Stratégie de polling (sans WebSocket)

Le compteur de notifications fraîches est maintenu par un **poll passif de 60 secondes** via `router.reload({ only: ['notifications'] })`, uniquement quand l'onglet est visible (`document.visibilityState === 'visible'`).

```
Fréquence : 60s
Coût : 1 requête SQL COUNT sur unread_notifications
Condition : onglet actif uniquement (visibilitychange event)
```

Cela couvre le cas où : un enseignant corrige pendant que l'étudiant est sur une autre page — au retour, le badge est à jour en moins de 60s.

**Pas de WebSocket** : le coût infrastructure (Pusher/Soketi + configuration) n'est pas justifié pour ce volume et cette criticité. Le polling est prévisible, observable, et débogable.

---

## 6. Découpage en tâches (implémentation)

### Backend (dans l'ordre)

- [ ] `php artisan notifications:table && php artisan migrate`
- [ ] `AssessmentPublishedNotification` + déclencheur dans `AssessmentService`
- [ ] `AssessmentGradedNotification` + déclencheur dans `ScoringService`
- [ ] `AssessmentSubmittedNotification` + déclencheur dans le contrôleur submit
- [ ] `AssessmentStartingSoonNotification` + commande `notifications:send-reminders`
- [ ] `NotificationController` (index, markRead, markAllRead)
- [ ] Routes dans `web.php`
- [ ] `HandleInertiaRequests` — ajout du `unread_count` lazy

### Frontend

- [ ] Type TypeScript `AppNotification` dans `resources/ts/types/`
- [ ] Hook `useNotifications` (fetch + markRead + poll)
- [ ] Composant `NotificationBell` + dropdown
- [ ] Injection dans `AuthenticatedLayout`

### Tests

- [ ] Test unitaire par notification (données JSON correctes)
- [ ] Test feature : publication → notification créée pour chaque étudiant inscrit
- [ ] Test feature : correction → notification étudiant
- [ ] Test feature : soumission → notification enseignant
- [ ] Test feature : `NotificationController::markRead`

---

## 7. Décisions ouvertes

| Question | Options | Recommandation |
|---|---|---|
| Faut-il une page dédiée `/notifications` ? | Oui / Panneau latéral / Dropdown | Panneau slide-over droit (100% height) pour V1, page dédiée en V2 |
| Purge des vieilles notifications ? | Manuelle / Auto après 30j | Commande schedulée mensuelle, `read_at IS NOT NULL AND created_at < now()-30d` |
| Notification pour l'admin ? | Inclure / Exclure scope V1 | Exclure V1, uniquement student + teacher |
| Préférences de notification (opt-out mail) ? | V1 / V2 | V2 uniquement |

---

## 8. Structure de fichiers cible

```
app/
  Console/Commands/
    SendAssessmentReminders.php          ← nouveau
  Http/Controllers/
    NotificationController.php           ← nouveau
  Notifications/
    UserCredentialsNotification.php      ← existant
    AssessmentPublishedNotification.php  ← nouveau
    AssessmentGradedNotification.php     ← nouveau
    AssessmentSubmittedNotification.php  ← nouveau
    AssessmentStartingSoonNotification.php ← nouveau

resources/ts/
  types/
    models/
      notification.ts                    ← nouveau
  hooks/
    shared/
      useNotifications.ts               ← nouveau
  Components/
    features/
      notifications/
        NotificationBell.tsx            ← nouveau (icône + badge compteur)
        NotificationItem.tsx            ← nouveau (une ligne de notification)
        NotificationPanel.tsx           ← nouveau (slide-over 100vh, liste + actions)
        index.ts                        ← nouveau
```
