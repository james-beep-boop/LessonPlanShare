<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user after an administrator resets their password.
 *
 * Notifies the account holder so they are aware of the change and
 * can contact the admin if the action was unexpected.
 */
class PasswordChangedByAdminNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your ARES Education Password Has Been Reset')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('An administrator has reset your account password on ARES Education.')
            ->line('Please sign in using the new password provided by the administrator.')
            ->action('Sign In', route('login'))
            ->line('If you did not expect this change, please contact the administrator.');
    }
}
