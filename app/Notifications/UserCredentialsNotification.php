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
        public string $role
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
        $roleName = __($this->role);

        return (new MailMessage)
            ->subject(__('Your Login Credentials - Examena'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your :role account has been successfully created on the Examena platform.', ['role' => $roleName]))
            ->line(__('Here are your login credentials:'))
            ->line("**" . __('Email:') . "** {$notifiable->email}")
            ->line("**" . __('Temporary Password:') . "** {$this->password}")
            ->action(__('Log In'), url('/login'))
            ->line(__('For security reasons, we recommend changing your password after your first login.'))
            ->line(__('If you did not request this account, please contact the administrator.'));
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
}
