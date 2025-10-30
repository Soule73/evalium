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
        $roleNames = [
            'student' => 'étudiant',
            'teacher' => 'enseignant',
            'admin' => 'administrateur',
            'super_admin' => 'super administrateur',
        ];

        $roleName = $roleNames[$this->role] ?? $this->role;

        return (new MailMessage)
            ->subject('Vos identifiants de connexion - Examena')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre compte {$roleName} a été créé avec succès sur la plateforme Examena.")
            ->line('Voici vos identifiants de connexion :')
            ->line("**Email :** {$notifiable->email}")
            ->line("**Mot de passe temporaire :** {$this->password}")
            ->action('Se connecter', url('/login'))
            ->line('Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe après votre première connexion.')
            ->line('Si vous n\'avez pas demandé la création de ce compte, veuillez contacter l\'administrateur.');
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
