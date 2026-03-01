<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $password,
        public string $role,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? config('app.locale', 'en');
        $roleKey = $this->resolveRoleKey();

        $subject = trans("notifications.credentials.subject_{$roleKey}", [], $locale)
            ?: trans('notifications.credentials.subject_default', [], $locale);

        $intro = trans("notifications.credentials.intro_{$roleKey}", [], $locale)
            ?: trans('notifications.credentials.intro_default', [], $locale);

        $features = trans("notifications.credentials.features_{$roleKey}", [], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting(trans('notifications.credentials.greeting', ['name' => $notifiable->name], $locale))
            ->line($intro)
            ->line($features)
            ->line(trans('notifications.credentials.credentials_intro', [], $locale))
            ->line(trans('notifications.credentials.email_label', ['email' => $notifiable->email], $locale))
            ->line(trans('notifications.credentials.password_label', ['password' => $this->password], $locale))
            ->action(trans('notifications.credentials.action', [], $locale), url('/login'))
            ->line(trans('notifications.credentials.security_hint', [], $locale))
            ->line(trans('notifications.credentials.disclaimer', [], $locale));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'email' => $notifiable->email,
            'role' => $this->role,
        ];
    }

    /**
     * Resolves the role to a translation key segment.
     * Maps 'super_admin' and 'admin' both to 'admin'.
     */
    private function resolveRoleKey(): string
    {
        return match ($this->role) {
            'student' => 'student',
            'teacher' => 'teacher',
            'admin', 'super_admin' => 'admin',
            default => 'default',
        };
    }
}
