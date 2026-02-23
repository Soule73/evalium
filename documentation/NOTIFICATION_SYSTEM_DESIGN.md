# Notification System — Design & Implementation Record

**Status:** Implemented  
**Date:** 23 February 2026  
**Commit:** `1bcab47` (branch `develop/v1.1-improvements`)

---

## 1. Overview

The notification system provides in-app, persistent notifications for students and teachers. It uses Laravel's built-in `database` notification channel — no external dependencies (no Pusher, no WebSockets). Notifications are delivered asynchronously via queued jobs and surfaced in a slide-over panel accessible from the application header.

---

## 2. Delivery Channel

### `database` (in-app) — only channel in V1

Laravel's `database` channel writes each notification to the `notifications` table as a JSON payload with a `read_at` timestamp. The channel was chosen because:

- No third-party service required
- Notifications persist across sessions — users see them after re-login
- Native Laravel API: `$user->unreadNotifications`, `$user->notifications()->paginate()`
- Built-in read/unread state management

### `mail` — deferred to V2

The `mail` infrastructure is already in place (`UserCredentialsNotification`). Per-user opt-in email delivery will be added in V2.

---

## 3. Notification Types

### Implemented (V1)

| Class | Recipient | Trigger | Queued |
|---|---|---|---|
| `AssessmentPublishedNotification` | Enrolled active students | Assessment published (`is_published = true`) | Yes |
| `AssessmentGradedNotification` | Student | Teacher submits corrections (`graded_at` set) | Yes |
| `AssessmentSubmittedNotification` | Teacher (class-subject owner) | Student submits an assessment | Yes |
| `AssessmentStartingSoonNotification` | Enrolled active students | 13–16 minutes before `scheduled_at` | Yes |

### Planned (V2)

| Class | Recipient | Trigger |
|---|---|---|
| `HomeworkDueSoonNotification` | Student | 24h before homework `due_date` |
| `EnrollmentNotification` | Student | Enrolled in a class |

---

## 4. Backend Architecture

### 4.1 Database Table

Standard Laravel notifications table, created via:

```bash
php artisan notifications:table
php artisan migrate
```

Schema:

| Column | Type | Notes |
|---|---|---|
| `id` | `uuid` | Primary key |
| `type` | `string` | Fully-qualified notification class name |
| `notifiable_type` | `string` | Polymorphic: always `App\Models\User` |
| `notifiable_id` | `bigint` | User ID |
| `data` | `json` | Notification payload (see §4.2) |
| `read_at` | `timestamp\|null` | `null` = unread |
| `created_at` / `updated_at` | `timestamp` | Standard Laravel timestamps |

### 4.2 Notification Classes

All four notification classes follow the same structure:

```php
class AssessmentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Assessment $assessment) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'             => 'assessment_published',  // snake_case slug — used by frontend
            'assessment_id'    => $this->assessment->id,
            'assessment_title' => $this->assessment->title,
            'subject'          => $this->assessment->classSubject?->subject?->name,
            'scheduled_at'     => $this->assessment->scheduled_at?->toIso8601String(),
            'delivery_mode'    => $this->assessment->delivery_mode,
            'url'              => route('student.assessments.show', $this->assessment->id),
        ];
    }
}
```

**`data.type` convention:** a `snake_case` slug used by the frontend to map a human-readable label and, in future iterations, an icon. All payloads include a `url` key that the UI navigates to when the notification is clicked.

### 4.3 Trigger Points

| Trigger | Location | Code |
|---|---|---|
| Assessment published | `AssessmentService::publishAssessment()` | `Notification::send($activeStudents, new AssessmentPublishedNotification($assessment))` |
| Assessment graded | `ScoringService::saveManualCorrection()` | `$student->notify(new AssessmentGradedNotification($assessment, $assignment))` |
| Assessment submitted | `StudentAssessmentController::submit()` | `$teacher->notify(new AssessmentSubmittedNotification($assessment, $assignment))` |
| Starting soon (−15 min) | `SendAssessmentReminders` command | Runs every 5 minutes via scheduler |

The `AssessmentPublishedNotification` targets only students whose enrollment status is `active` in the assessment's class.

### 4.4 Starting-Soon Reminder Command

```
app/Console/Commands/SendAssessmentReminders.php
Artisan signature: notifications:send-reminders
Scheduler: every 5 minutes (see routes/console.php)
```

The command queries assessments where `scheduled_at` falls in the `[now+13min, now+16min]` window (3-minute tolerance to account for scheduler jitter). It notifies all active students enrolled in the assessment's class.

```php
Schedule::command('notifications:send-reminders')->everyFiveMinutes();
```

### 4.5 API Routes

All routes are nested inside the authenticated (`auth`) middleware group:

```php
Route::prefix('notifications')
    ->name('notifications.')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('/',           'index')->name('index');         // list + unread count
        Route::post('/{id}/read', 'markRead')->name('read');       // mark one as read
        Route::post('/read-all',  'markAllRead')->name('read-all');// mark all as read
        Route::delete('/{id}',    'destroy')->name('delete');      // delete one
        Route::delete('/',        'destroyAll')->name('delete-all');// delete all
    });
```

All endpoints return JSON. `index` paginates 20 notifications per page and returns:

```json
{
  "notifications": [...],
  "unread_count": 3,
  "has_more": false
}
```

`destroy` and `destroyAll` scope all queries to the authenticated user — requesting another user's notification returns `404`.

### 4.6 Unread Count Sharing via Inertia (Lazy)

`app/Http/Middleware/HandleInertiaRequests.php` shares a `notifications` key using `Inertia::lazy()`:

```php
'notifications' => Inertia::lazy(fn () => [
    'unread_count' => $user?->unreadNotifications()->count() ?? 0,
]),
```

`Inertia::lazy()` ensures the SQL query only runs when a reload explicitly requests `only: ['notifications']`. This prevents an extra DB query on every single page load.

---

## 5. Frontend Architecture

### 5.1 File Structure

```
resources/ts/
  types/
    models/
      notification.ts          # AppNotification interface
  hooks/
    shared/
      useNotifications.ts      # state management hook
  Components/
    features/
      notifications/
        NotificationBell.tsx   # bell icon + badge, opens panel
        NotificationPanel.tsx  # slide-over (100vh, right side)
        NotificationItem.tsx   # single notification row
        index.ts               # barrel export
```

### 5.2 TypeScript Type (`AppNotification`)

```typescript
interface AppNotification {
    id: string;
    type: string;
    data: {
        type: string;
        assessment_id: number;
        assessment_title: string;
        subject?: string;
        scheduled_at?: string;
        delivery_mode?: string;
        url: string;
    };
    read_at: string | null;
    created_at: string;
}
```

### 5.3 `useNotifications` Hook

Manages all notification state in a single hook exposed through `NotificationBell`:

| Function | Behaviour |
|---|---|
| `fetchNotifications()` | Calls `GET /notifications`, sets state. Called lazily on first panel open. |
| `markRead(id)` | **Optimistic update first** (state updated synchronously), then `POST /notifications/{id}/read` with `keepalive: true`. |
| `markAllRead()` | `POST /notifications/read-all`, awaits response, then updates state. |
| `deleteNotification(id)` | **Optimistic update first**, then `DELETE /notifications/{id}` with `keepalive: true`. |
| `deleteAll()` | **Optimistic update first**, then `DELETE /notifications` with `keepalive: true`. |

**Why optimistic updates + `keepalive: true`?**
Clicking a notification triggers Inertia's `router.visit()`, which can cancel in-flight `fetch` requests. With `keepalive: true`, the browser buffers the request and sends it even after the page navigates away. Updating state first makes the UI feel instant regardless of network timing.

**Why `markAllRead` is not optimistic?**
`markAllRead` does not navigate away, so it can await the server response before updating state — producing more consistent UX (the badge drops to 0 only once confirmed).

### 5.4 Polling Strategy

The hook sets up a 60-second passive poll using `router.reload({ only: ['notifications'] })`:

```typescript
useEffect(() => {
    const reload = () => {
        if (document.visibilityState === 'visible') {
            router.reload({ only: ['notifications'] });
        }
    };

    const interval = setInterval(reload, 60_000);
    document.addEventListener('visibilitychange', reload);

    return () => {
        clearInterval(interval);
        document.removeEventListener('visibilitychange', reload);
    };
}, []);
```

- Runs only when the tab is visible (`visibilitychange` guard)
- Cost: 1 SQL `COUNT` on `unread_notifications` per minute
- Covers the "teacher grades while student is on another page" case: badge refreshes within ≤60s on return

**No WebSockets:** Pusher/Soketi infrastructure cost is not justified for this volume and criticality. Polling is predictable, testable, and observable.

### 5.5 UI Components

#### `NotificationBell`

- Renders the bell icon with a red badge showing the unread count
- Badge source **before panel opens:** `auth.notifications.unread_count` from Inertia shared data (no extra fetch on mount)
- Badge source **after panel opens:** live `unreadCount` from `useNotifications` state
- On click: calls `fetchNotifications()` once, then opens the panel

#### `NotificationPanel`

- Fixed slide-over on the right side, full viewport height (`h-screen w-full max-w-sm`)
- Dark overlay (`bg-black/30`) behind the panel closes it on click
- Header actions:
  - **Mark all as read** — shown only if `unreadCount > 0`
  - **Delete all** — shown only if at least one notification exists
  - **Close (×)**
- Skeleton loading state (5 pulsing rows) while `loading && notifications.length === 0`
- Empty state with bell icon when no notifications exist

#### `NotificationItem`

- Unread row: `bg-blue-50` background + blue dot indicator
- Read row: `bg-white` background
- Clicking the row: calls `onRead(id)` (if unread) then `router.visit(data.url)`
- **Trash icon button:** appears on row hover (`group-hover:opacity-100`), calls `onDelete(id)` without navigating

### 5.6 CSRF Token

`resources/views/app.blade.php` includes the CSRF meta tag required by the raw `fetch()` calls in the hook:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

All mutating `fetch()` calls read it as:

```typescript
'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
```

---

## 6. `composer run dev` Integration

The `dev` script runs four concurrent processes:

```json
"dev": "npx concurrently ... \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan schedule:work\" \"yarn dev\" --names=server,queue,scheduler,vite"
```

- `queue:listen` processes queued notification jobs
- `schedule:work` fires the `notifications:send-reminders` command every 5 minutes

---

## 7. Test Coverage

All tests are in `tests/Feature/Notifications/AssessmentNotificationsTest.php` (12 tests, 34 assertions):

| Test | Scenario |
|---|---|
| `test_notification_index_returns_user_notifications` | Index returns correct JSON structure and unread count |
| `test_notification_index_requires_auth` | Unauthenticated request returns 401 |
| `test_mark_notification_as_read` | `read_at` is set after POST |
| `test_mark_all_notifications_as_read` | All `read_at` updated in bulk |
| `test_publish_assessment_sends_notification_to_active_students` | Notification dispatched to each active student |
| `test_publish_assessment_does_not_notify_withdrawn_students` | Withdrawn enrollment receives no notification |
| `test_assessment_published_notification_to_database_has_expected_shape` | JSON payload structure |
| `test_assessment_graded_notification_to_database_has_expected_shape` | JSON payload structure |
| `test_assessment_submitted_notification_to_database_has_expected_shape` | JSON payload structure |
| `test_delete_single_notification` | Notification is removed from DB |
| `test_delete_notification_belonging_to_another_user_returns_404` | Cross-user deletion is blocked with 404 |
| `test_delete_all_notifications` | All user notifications deleted |

---

## 8. Open Decisions & Future Work

| Topic | V1 Decision | V2 Candidate |
|---|---|---|
| Dedicated `/notifications` page | No — slide-over panel only | Full paginated page with filters |
| Notification purge | Not automated | Monthly scheduled command: `read_at IS NOT NULL AND created_at < now()-30d` |
| Admin notifications | Excluded | Admin-specific types (enrollment requests, etc.) |
| Mail opt-in | Excluded | Per-user preference toggle |
| Real-time delivery | 60s polling | Laravel Reverb / Soketi if volume justifies it |
